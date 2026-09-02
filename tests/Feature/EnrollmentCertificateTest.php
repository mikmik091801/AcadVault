<?php

namespace Tests\Feature;

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\EnrollmentDocument;
use App\Models\Student;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\EnrollmentCertificateService;
use App\Support\EnrollmentFingerprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EnrollmentCertificateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function enrolledStudent(int $classes = 2): Student
    {
        $student = Student::factory()->create();

        foreach (range(1, $classes) as $i) {
            Enrollment::factory()->create([
                'student_id' => $student->id,
                // Codes come from the factory: this helper runs twice in some
                // tests and `courses.code` is unique.
                'course_id' => Course::factory()->create()->id,
            ]);
        }

        return $student;
    }

    // ------------------------------------------------------------------
    // Issuing
    // ------------------------------------------------------------------

    public function test_a_student_issues_a_certificate_of_their_classes(): void
    {
        $student = $this->enrolledStudent(3);

        $this->actingAs($student->user)
            ->post(route('enrollment-documents.store'))
            ->assertRedirect();

        $document = EnrollmentDocument::firstOrFail();

        $this->assertSame($student->id, $document->student_id);
        $this->assertSame(3, $document->class_count);
        $this->assertSame(64, strlen($document->file_hash));
        $this->assertNotNull($document->qr_code_path);

        Storage::disk('public')->assertExists($document->qr_code_path);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $student->user->id,
            'action' => AuditLogger::CERTIFICATE_ISSUED,
        ]);
    }

    public function test_a_certificate_cannot_be_issued_with_no_classes(): void
    {
        $student = Student::factory()->create();

        $this->actingAs($student->user)
            ->post(route('enrollment-documents.store'))
            ->assertSessionHasErrors('certificate');

        $this->assertSame(0, EnrollmentDocument::count());
    }

    public function test_a_dropped_class_is_left_off_the_certificate(): void
    {
        $student = $this->enrolledStudent(2);
        $student->enrollments()->first()->update(['status' => EnrollmentStatus::Dropped]);

        $this->actingAs($student->user)->post(route('enrollment-documents.store'));

        $this->assertSame(1, EnrollmentDocument::firstOrFail()->class_count);
    }

    // ------------------------------------------------------------------
    // Who can see it
    // ------------------------------------------------------------------

    public function test_a_student_cannot_open_another_students_certificate(): void
    {
        $mine = $this->enrolledStudent();
        $theirs = $this->enrolledStudent();

        $document = app(EnrollmentCertificateService::class)->issue($theirs, $theirs->user);

        $this->actingAs($mine->user)
            ->get(route('enrollment-documents.show', $document))
            ->assertForbidden();

        $this->actingAs($theirs->user)
            ->get(route('enrollment-documents.show', $document))
            ->assertOk();
    }

    public function test_staff_can_open_any_certificate(): void
    {
        $student = $this->enrolledStudent();
        $document = app(EnrollmentCertificateService::class)->issue($student, $student->user);

        $this->actingAs(User::factory()->registrar()->create())
            ->get(route('enrollment-documents.show', $document))
            ->assertOk();
    }

    // ------------------------------------------------------------------
    // The PDF
    // ------------------------------------------------------------------

    public function test_the_certificate_pdf_carries_the_logo_and_qr_code(): void
    {
        $student = $this->enrolledStudent();
        $document = app(EnrollmentCertificateService::class)->issue($student, $student->user);

        $pdf = $this->actingAs($student->user)
            ->get(route('enrollment-documents.download', $document))
            ->assertOk()
            ->getContent();

        $this->assertStringStartsWith('%PDF-', $pdf);

        // Same guarantee as the grade export: a dropped image leaves the text
        // intact, so only the image count catches it.
        $this->assertSame(2, substr_count($pdf, '/Subtype /Image'));
    }

    // ------------------------------------------------------------------
    // Public verification
    // ------------------------------------------------------------------

    public function test_anyone_can_verify_a_certificate_without_signing_in(): void
    {
        $student = $this->enrolledStudent();
        $document = app(EnrollmentCertificateService::class)->issue($student, $student->user);

        $this->get(route('verify.enrollment', $document->uuid))
            ->assertOk()
            ->assertSee('This certificate is current');

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLogger::CERTIFICATE_VERIFIED,
        ]);
    }

    public function test_an_unknown_code_reports_as_invalid_rather_than_404(): void
    {
        $this->get(route('verify.enrollment', 'not-a-real-uuid'))
            ->assertOk()
            ->assertSee('could not be found');
    }

    public function test_enrolling_again_supersedes_an_earlier_certificate(): void
    {
        $student = $this->enrolledStudent(1);
        $document = app(EnrollmentCertificateService::class)->issue($student, $student->user);

        // Verifies clean before anything changes.
        $this->get(route('verify.enrollment', $document->uuid))->assertSee('is current');

        $this->actingAs($student->user)->post(route('enrollments.store'), [
            'course_id' => Course::factory()->create()->id,
        ]);

        $this->get(route('verify.enrollment', $document->uuid))
            ->assertOk()
            ->assertSee('out of date')
            // Wording matters: this is a superseded document, not a forged one.
            ->assertDontSee('Tampered');
    }

    public function test_an_approved_drop_supersedes_an_earlier_certificate(): void
    {
        $student = $this->enrolledStudent(2);
        $document = app(EnrollmentCertificateService::class)->issue($student, $student->user);

        $enrollment = $student->enrollments()->first();
        $enrollment->update(['status' => EnrollmentStatus::DropPending]);

        // Even a *pending* drop supersedes the certificate. The PDF prints a
        // status column, so a document still reading "Enrolled" for a class
        // the student has asked to drop would be misleading — the fingerprint
        // covers status for exactly that reason.
        $this->get(route('verify.enrollment', $document->uuid))->assertSee('out of date');

        $this->actingAs(User::factory()->registrar()->create())
            ->patch(route('drop-requests.update', $enrollment), ['decision' => 'approve']);

        $this->get(route('verify.enrollment', $document->uuid))->assertSee('out of date');

        // Re-issuing after the change gives a certificate that verifies clean.
        $fresh = app(EnrollmentCertificateService::class)->issue($student->fresh(), $student->user);
        $this->get(route('verify.enrollment', $fresh->uuid))->assertSee('is current');
    }

    public function test_the_fingerprint_ignores_the_order_rows_come_back_in(): void
    {
        $student = $this->enrolledStudent(3);

        $first = EnrollmentFingerprint::hash($student);

        // Touching rows changes their natural ordering; the hash must not move.
        $student->enrollments()->orderByDesc('id')->first()->touch();

        $this->assertSame($first, EnrollmentFingerprint::hash($student->fresh()));
    }
}
