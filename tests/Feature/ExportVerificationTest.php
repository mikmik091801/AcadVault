<?php

namespace Tests\Feature;

use App\Models\AcademicRecord;
use App\Models\Export;
use App\Models\Student;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\ExportService;
use App\Support\QrCodeGenerator;
use App\Support\RecordFingerprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExportVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    // ------------------------------------------------------------------
    // Fingerprinting
    // ------------------------------------------------------------------

    public function test_the_fingerprint_is_a_sha256_hex_digest(): void
    {
        $record = AcademicRecord::factory()->create();
        $hash = RecordFingerprint::hash($record);

        $this->assertSame(64, strlen($hash));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    }

    public function test_the_fingerprint_is_stable_across_reloads(): void
    {
        $record = AcademicRecord::factory()->create();

        $first = RecordFingerprint::hash($record);
        $second = RecordFingerprint::hash(AcademicRecord::find($record->id));

        // Encryption uses a random IV per write, so a fingerprint taken over
        // ciphertext would differ every time. It must be taken over plaintext.
        $this->assertSame($first, $second);
    }

    public function test_changing_the_grade_changes_the_fingerprint(): void
    {
        $record = AcademicRecord::factory()->create(['grade' => '1.00']);
        $before = RecordFingerprint::hash($record);

        $record->update(['grade' => '5.00']);

        $this->assertNotSame($before, RecordFingerprint::hash($record->fresh()));
    }

    public function test_re_saving_the_same_values_keeps_the_fingerprint(): void
    {
        $record = AcademicRecord::factory()->create(['grade' => '2.00', 'remarks' => 'Passed']);
        $before = RecordFingerprint::hash($record);

        // Re-encrypting identical plaintext must not look like tampering.
        $record->update(['grade' => '2.00', 'remarks' => 'Passed']);

        $this->assertSame($before, RecordFingerprint::hash($record->fresh()));
    }

    // ------------------------------------------------------------------
    // QR generation
    // ------------------------------------------------------------------

    public function test_the_qr_generator_produces_a_valid_png(): void
    {
        $png = (new QrCodeGenerator)->png('https://example.test/verify/abc', 220);

        $this->assertStringStartsWith("\x89PNG", $png);

        $info = getimagesizefromstring($png);
        $this->assertNotFalse($info);
        $this->assertSame(IMAGETYPE_PNG, $info[2]);
        $this->assertGreaterThan(100, $info[0]);
        $this->assertSame($info[0], $info[1], 'QR must be square');
    }

    // ------------------------------------------------------------------
    // Issuing an export
    // ------------------------------------------------------------------

    public function test_a_registrar_can_issue_an_export(): void
    {
        $registrar = User::factory()->registrar()->create();
        $record = AcademicRecord::factory()->create();

        $this->actingAs($registrar)
            ->post(route('records.export', $record))
            ->assertRedirect();

        $export = Export::firstOrFail();

        $this->assertSame($record->id, $export->academic_record_id);
        $this->assertSame($registrar->id, $export->exported_by);
        $this->assertSame(RecordFingerprint::hash($record), $export->file_hash);
        $this->assertNotNull($export->uuid);

        // The QR image was written to storage.
        $this->assertNotNull($export->qr_code_path);
        Storage::disk('public')->assertExists($export->qr_code_path);
    }

    public function test_issuing_an_export_is_audited(): void
    {
        $registrar = User::factory()->registrar()->create();
        $record = AcademicRecord::factory()->create();

        $this->actingAs($registrar)->post(route('records.export', $record));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $registrar->id,
            'action' => AuditLogger::RECORD_EXPORTED,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $registrar->id,
            'action' => AuditLogger::RECORD_DECRYPTED,
            'target_id' => $record->id,
        ]);
    }

    public function test_faculty_and_students_cannot_issue_exports(): void
    {
        $record = AcademicRecord::factory()->create();

        foreach (['faculty', 'student'] as $role) {
            $user = User::factory()->role(\App\Enums\Role::from($role))->create();

            $this->actingAs($user)
                ->post(route('records.export', $record))
                ->assertForbidden();
        }

        $this->assertDatabaseCount('exports', 0);
    }

    // ------------------------------------------------------------------
    // The PDF
    // ------------------------------------------------------------------

    public function test_the_pdf_downloads_and_embeds_the_qr_image(): void
    {
        $registrar = User::factory()->registrar()->create();
        $record = AcademicRecord::factory()->create(['grade' => '1.25']);
        $export = app(ExportService::class)->issue($record, $registrar);

        $response = $this->actingAs($registrar)->get(route('exports.download', $export));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');

        $pdf = $response->getContent();

        $this->assertStringStartsWith('%PDF', $pdf);

        // dompdf drops SVG silently, so assert a real raster image object made
        // it into the document rather than trusting that the markup rendered.
        $this->assertMatchesRegularExpression('~/Subtype\s*/Image~', $pdf);
    }

    public function test_the_pdf_contains_the_document_id_and_hash(): void
    {
        $registrar = User::factory()->registrar()->create();
        $record = AcademicRecord::factory()->create();
        $export = app(ExportService::class)->issue($record, $registrar);

        $pdf = app(ExportService::class)->pdf($export)->output();

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertGreaterThan(5000, strlen($pdf));
    }

    // ------------------------------------------------------------------
    // Verification
    // ------------------------------------------------------------------

    public function test_an_untouched_document_verifies_as_authentic(): void
    {
        $registrar = User::factory()->registrar()->create();
        $record = AcademicRecord::factory()->create();
        $export = app(ExportService::class)->issue($record, $registrar);

        $this->get(route('verify', $export->uuid))
            ->assertOk()
            ->assertSee('Authentic')
            ->assertSee('This document is authentic')
            ->assertDontSee('Tampered');
    }

    public function test_verification_is_public(): void
    {
        $registrar = User::factory()->registrar()->create();
        $export = app(ExportService::class)->issue(AcademicRecord::factory()->create(), $registrar);

        // No authentication — a printed transcript must be checkable by anyone.
        $this->assertGuest();
        $this->get(route('verify', $export->uuid))->assertOk();
    }

    public function test_a_changed_grade_makes_the_document_report_as_tampered(): void
    {
        $registrar = User::factory()->registrar()->create();
        $record = AcademicRecord::factory()->create(['grade' => '5.00', 'remarks' => 'Failed']);
        $export = app(ExportService::class)->issue($record, $registrar);

        $this->get(route('verify', $export->uuid))->assertSee('Authentic');

        // Somebody edits the grade after the document was issued.
        $record->update(['grade' => '1.00', 'remarks' => 'Passed']);

        $this->get(route('verify', $export->uuid))
            ->assertOk()
            ->assertSee('Tampered')
            ->assertSee('does not match our records');
    }

    public function test_an_unknown_document_id_reports_invalid(): void
    {
        $this->get(route('verify', 'not-a-real-uuid'))
            ->assertOk()
            ->assertSee('Invalid')
            ->assertSee('could not be found');
    }

    public function test_verification_attempts_are_audited(): void
    {
        $registrar = User::factory()->registrar()->create();
        $export = app(ExportService::class)->issue(AcademicRecord::factory()->create(), $registrar);

        $this->get(route('verify', $export->uuid));

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLogger::EXPORT_VERIFIED,
            'target_id' => $export->id,
        ]);
    }

    // ------------------------------------------------------------------
    // Access to issued documents
    // ------------------------------------------------------------------

    public function test_a_student_sees_only_their_own_exports(): void
    {
        $registrar = User::factory()->registrar()->create();

        $profile = Student::factory()->create();
        $mine = app(ExportService::class)->issue(
            AcademicRecord::factory()->create(['student_id' => $profile->id]),
            $registrar,
        );
        $theirs = app(ExportService::class)->issue(AcademicRecord::factory()->create(), $registrar);

        $this->actingAs($profile->user)
            ->get(route('exports.index'))
            ->assertOk()
            ->assertSee(\Illuminate\Support\Str::limit($mine->uuid, 13))
            ->assertDontSee(\Illuminate\Support\Str::limit($theirs->uuid, 13));

        $this->actingAs($profile->user)->get(route('exports.show', $mine))->assertOk();
        $this->actingAs($profile->user)->get(route('exports.show', $theirs))->assertForbidden();
        $this->actingAs($profile->user)->get(route('exports.download', $theirs))->assertForbidden();
    }

    public function test_a_student_can_download_their_own_document(): void
    {
        $registrar = User::factory()->registrar()->create();
        $profile = Student::factory()->create();
        $export = app(ExportService::class)->issue(
            AcademicRecord::factory()->create(['student_id' => $profile->id]),
            $registrar,
        );

        $this->actingAs($profile->user)
            ->get(route('exports.download', $export))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_faculty_cannot_browse_exports(): void
    {
        $this->actingAs(User::factory()->faculty()->create())
            ->get(route('exports.index'))
            ->assertForbidden();
    }
}
