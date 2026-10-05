<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\AcademicRecord;
use App\Models\Course;
use App\Models\Export;
use App\Models\Student;
use App\Models\User;
use App\Support\RecordFingerprint;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);
    }

    public function test_it_seeds_one_account_per_role(): void
    {
        foreach (Role::cases() as $role) {
            $this->assertGreaterThanOrEqual(
                1,
                User::where('role', $role)->count(),
                "expected at least one {$role->value}",
            );
        }

        $this->assertSame(1, User::where('role', Role::Admin)->count());
        $this->assertSame(1, User::where('role', Role::Registrar)->count());
        $this->assertSame(1, User::where('role', Role::Faculty)->count());
    }

    public function test_the_demo_accounts_can_sign_in(): void
    {
        $emails = [
            'admin@acadvault.test',
            'registrar@acadvault.test',
            'faculty@acadvault.test',
            'student@acadvault.test',
        ];

        foreach ($emails as $email) {
            $user = User::where('email', $email)->firstOrFail();

            $this->assertTrue(
                Hash::check('password', $user->password),
                "{$email} should sign in with the documented password",
            );
        }
    }

    public function test_seeded_passwords_use_argon2id(): void
    {
        // The seeder must pass plain strings so the `hashed` cast applies the
        // configured driver; a bcrypt() call there would throw.
        foreach (User::all() as $user) {
            $this->assertStringStartsWith('$argon2id$', $user->password);
        }
    }

    public function test_it_seeds_courses_with_and_without_an_instructor(): void
    {
        $this->assertGreaterThanOrEqual(3, Course::count());
        $this->assertTrue(Course::whereNotNull('faculty_id')->exists());
        $this->assertTrue(Course::whereNull('faculty_id')->exists());
    }

    public function test_it_seeds_records_that_decrypt_correctly(): void
    {
        $this->assertGreaterThanOrEqual(10, AcademicRecord::count());

        $record = AcademicRecord::whereHas(
            'student',
            fn ($s) => $s->where('student_number', '2026-01001'),
        )->firstOrFail();

        // Readable through the model despite being ciphertext at rest.
        $this->assertMatchesRegularExpression('/^\d\.\d{2}$/', $record->grade);
        $this->assertNotEmpty($record->remarks);
    }

    public function test_every_seeded_record_has_a_creator_and_relations(): void
    {
        foreach (AcademicRecord::with(['student', 'course', 'creator'])->get() as $record) {
            $this->assertNotNull($record->student, 'record must belong to a student');
            $this->assertNotNull($record->course, 'record must belong to a course');
            $this->assertNotNull($record->creator, 'record must record who entered it');
            $this->assertSame(Role::Registrar, $record->creator->role);
        }
    }

    public function test_the_primary_student_account_has_a_profile(): void
    {
        $student = User::where('email', 'student@acadvault.test')->firstOrFail();

        $this->assertNotNull($student->student);
        $this->assertSame('2026-01001', $student->student->student_number);
        $this->assertGreaterThan(0, $student->student->academicRecords()->count());
    }

    public function test_it_seeds_verifiable_exports(): void
    {
        $this->assertGreaterThanOrEqual(1, Export::count());

        foreach (Export::with('academicRecord')->get() as $export) {
            $this->assertNotNull($export->uuid);
            $this->assertSame(64, strlen($export->file_hash));

            // Seeded documents must verify as authentic out of the box.
            $this->assertSame(
                RecordFingerprint::hash($export->academicRecord),
                $export->file_hash,
            );

            $this->get(route('verify', $export->uuid))
                ->assertOk()
                ->assertSee('Authentic');
        }
    }

    public function test_seeding_writes_audit_entries(): void
    {
        // Model events are left enabled in DatabaseSeeder so the audit screen
        // has content immediately after a fresh seed.
        $this->assertDatabaseHas('audit_logs', ['action' => 'record.created']);
    }

    public function test_seeding_twice_does_not_duplicate_data(): void
    {
        $before = [
            'users' => User::count(),
            'students' => Student::count(),
            'courses' => Course::count(),
            'records' => AcademicRecord::count(),
            'exports' => Export::count(),
        ];

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($before['users'], User::count());
        $this->assertSame($before['students'], Student::count());
        $this->assertSame($before['courses'], Course::count());
        $this->assertSame($before['records'], AcademicRecord::count());
        $this->assertSame($before['exports'], Export::count());
    }

    public function test_each_seeded_role_can_reach_its_own_dashboard(): void
    {
        $expectations = [
            'admin@acadvault.test' => 'Total users',
            'registrar@acadvault.test' => 'Drop requests',
            'faculty@acadvault.test' => 'My courses',
            'student@acadvault.test' => 'My records',
        ];

        foreach ($expectations as $email => $expectedCard) {
            $user = User::where('email', $email)->firstOrFail();

            $this->actingAs($user)
                ->get(route('dashboard'))
                ->assertOk()
                ->assertSee($expectedCard);
        }
    }
}
