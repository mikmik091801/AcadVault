<?php

namespace Tests\Feature;

use App\Models\AcademicRecord;
use App\Models\Student;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EncryptionTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------------
    // Field encryption at rest
    // ------------------------------------------------------------------

    public function test_grade_and_remarks_are_ciphertext_in_the_database(): void
    {
        $record = AcademicRecord::factory()->create([
            'grade' => '1.75',
            'remarks' => 'Passed with merit',
        ]);

        $raw = DB::table('academic_records')->where('id', $record->id)->first();

        // Nothing readable is stored.
        $this->assertNotSame('1.75', $raw->grade);
        $this->assertNotSame('Passed with merit', $raw->remarks);
        $this->assertStringNotContainsString('1.75', $raw->grade);
        $this->assertStringNotContainsString('Passed', $raw->remarks);

        // What is stored is a Laravel encryption envelope.
        $payload = json_decode(base64_decode($raw->grade), true);
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('iv', $payload);
        $this->assertArrayHasKey('value', $payload);
        $this->assertArrayHasKey('mac', $payload);
    }

    public function test_the_model_returns_decrypted_values(): void
    {
        $record = AcademicRecord::factory()->create([
            'grade' => '2.25',
            'remarks' => 'Conditional pass',
        ]);

        $fresh = AcademicRecord::find($record->id);

        $this->assertSame('2.25', $fresh->grade);
        $this->assertSame('Conditional pass', $fresh->remarks);
    }

    public function test_identical_grades_produce_different_ciphertext(): void
    {
        $a = AcademicRecord::factory()->create(['grade' => '1.00']);
        $b = AcademicRecord::factory()->create(['grade' => '1.00']);

        $rawA = DB::table('academic_records')->where('id', $a->id)->value('grade');
        $rawB = DB::table('academic_records')->where('id', $b->id)->value('grade');

        // A random IV per row means the ciphertext cannot be correlated to
        // reveal which students share a grade.
        $this->assertNotSame($rawA, $rawB);
        $this->assertSame('1.00', $a->fresh()->grade);
        $this->assertSame('1.00', $b->fresh()->grade);
    }

    public function test_tampering_with_stored_ciphertext_is_detected(): void
    {
        $record = AcademicRecord::factory()->create(['grade' => '5.00']);

        // Someone with database access swaps in a different encrypted value's
        // shape — the MAC no longer matches.
        $tampered = base64_encode(json_encode([
            'iv' => base64_encode(random_bytes(16)),
            'value' => base64_encode('forged'),
            'mac' => str_repeat('0', 64),
        ]));

        DB::table('academic_records')->where('id', $record->id)->update(['grade' => $tampered]);

        $this->expectException(DecryptException::class);
        AcademicRecord::find($record->id)->grade;
    }

    public function test_a_null_remark_stays_null(): void
    {
        $record = AcademicRecord::factory()->create(['remarks' => null]);

        $this->assertNull($record->fresh()->remarks);
        $this->assertNull(DB::table('academic_records')->where('id', $record->id)->value('remarks'));
    }

    public function test_updating_a_grade_re_encrypts_it(): void
    {
        $record = AcademicRecord::factory()->create(['grade' => '3.00']);

        $record->update(['grade' => '1.00']);

        $raw = DB::table('academic_records')->where('id', $record->id)->value('grade');

        $this->assertStringNotContainsString('1.00', $raw);
        $this->assertSame('1.00', Crypt::decryptString($raw));
        $this->assertSame('1.00', $record->fresh()->grade);
    }

    public function test_encrypted_records_still_render_in_the_ui(): void
    {
        $registrar = User::factory()->registrar()->create();
        $student = Student::factory()->create();
        $record = AcademicRecord::factory()->create([
            'student_id' => $student->id,
            'grade' => '1.50',
            'remarks' => 'Distinction',
        ]);

        $this->actingAs($registrar)
            ->get(route('records.show', $record))
            ->assertOk()
            ->assertSee('1.50')
            ->assertSee('Distinction');

        $this->actingAs($registrar)
            ->get(route('records.index'))
            ->assertOk()
            ->assertSee('1.50');
    }

    public function test_searching_records_still_works_alongside_encryption(): void
    {
        $registrar = User::factory()->registrar()->create();
        $student = Student::factory()->create(['student_number' => '2026-42424']);
        AcademicRecord::factory()->create(['student_id' => $student->id]);
        AcademicRecord::factory()->create();

        // Student/course columns remain searchable; grades deliberately are not.
        $this->actingAs($registrar)
            ->get(route('records.index', ['search' => '2026-42424']))
            ->assertOk()
            ->assertSee('2026-42424');
    }

    // ------------------------------------------------------------------
    // Password hashing
    // ------------------------------------------------------------------

    public function test_the_default_hash_driver_is_argon2id(): void
    {
        $this->assertSame('argon2id', config('hashing.driver'));

        $hash = Hash::make('a-test-password');

        $this->assertStringStartsWith('$argon2id$', $hash);
        $this->assertSame('argon2id', password_get_info($hash)['algoName']);
        $this->assertTrue(Hash::check('a-test-password', $hash));
    }

    public function test_new_user_passwords_are_stored_with_argon2id(): void
    {
        $user = User::create([
            'name' => 'Argon Tester',
            'email' => 'argon@acadvault.test',
            'password' => 'super-secret-password',
        ]);

        $this->assertStringStartsWith('$argon2id$', $user->fresh()->password);
        $this->assertTrue(Hash::check('super-secret-password', $user->fresh()->password));
    }

    public function test_legacy_bcrypt_passwords_still_verify(): void
    {
        // If config/hashing.php set argon.verify = true this would throw
        // instead, locking out every pre-migration account.
        $bcryptHash = Hash::driver('bcrypt')->make('legacy-password');

        $this->assertStringStartsWith('$2y$', $bcryptHash);
        $this->assertTrue(Hash::check('legacy-password', $bcryptHash));
        $this->assertFalse(Hash::check('wrong-password', $bcryptHash));
    }

    public function test_a_legacy_bcrypt_password_is_upgraded_to_argon2id_on_login(): void
    {
        $user = User::factory()->create();

        // Write the bcrypt hash straight to the row, exactly as it would have
        // been left behind by the app before the switch to Argon2id. (Assigning
        // it through the model would hit the `hashed` cast, which rejects a
        // non-Argon2id hash on write.)
        DB::table('users')->where('id', $user->id)->update([
            'password' => Hash::driver('bcrypt')->make('legacy-password'),
        ]);

        $user->refresh();
        $this->assertStringStartsWith('$2y$', $user->password);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'legacy-password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);

        // Rehash-on-login silently migrated the stored hash.
        $this->assertStringStartsWith('$argon2id$', $user->fresh()->password);
        $this->assertTrue(Hash::check('legacy-password', $user->fresh()->password));
    }

    public function test_production_argon_defaults_meet_the_owasp_minimum(): void
    {
        // The suite overrides the cost factors via phpunit.xml to stay fast,
        // so assert on the fallback defaults in the config file itself. This
        // guards against someone quietly weakening production hashing.
        $source = file_get_contents(config_path('hashing.php'));

        $this->assertStringContainsString("env('ARGON_MEMORY', 65536)", $source, 'memory should default to 64 MB');
        $this->assertStringContainsString("env('ARGON_TIME', 4)", $source);

        $this->assertSame('argon2id', config('hashing.driver'));
        $this->assertFalse(config('hashing.argon.verify'), 'verify must stay false so legacy hashes can be upgraded');
    }
}
