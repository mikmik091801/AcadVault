<?php

namespace Tests\Feature;

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------------
    // Access
    // ------------------------------------------------------------------

    public function test_only_students_reach_the_enrollment_portal(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('enrollments.index'))->assertForbidden();

        $this->actingAs(User::factory()->registrar()->create())
            ->get(route('enrollments.index'))->assertForbidden();

        $this->actingAs(User::factory()->faculty()->create())
            ->get(route('enrollments.index'))->assertForbidden();

        $this->actingAs(Student::factory()->create()->user)
            ->get(route('enrollments.index'))->assertOk();
    }

    public function test_only_staff_reach_the_drop_request_queue(): void
    {
        $this->actingAs(Student::factory()->create()->user)
            ->get(route('drop-requests.index'))->assertForbidden();

        $this->actingAs(User::factory()->faculty()->create())
            ->get(route('drop-requests.index'))->assertForbidden();

        $this->actingAs(User::factory()->registrar()->create())
            ->get(route('drop-requests.index'))->assertOk();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('drop-requests.index'))->assertOk();
    }

    // ------------------------------------------------------------------
    // Enrolling — immediate, no approval
    // ------------------------------------------------------------------

    public function test_a_student_enrolls_themselves_without_approval(): void
    {
        $profile = Student::factory()->create();
        $course = Course::factory()->create(['code' => 'CS101']);

        $this->actingAs($profile->user)
            ->post(route('enrollments.store'), ['course_id' => $course->id])
            ->assertRedirect(route('enrollments.index'));

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $profile->id,
            'course_id' => $course->id,
            'status' => EnrollmentStatus::Enrolled->value,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $profile->user->id,
            'action' => AuditLogger::ENROLLED,
        ]);
    }

    public function test_a_student_cannot_enroll_twice_in_the_same_class(): void
    {
        $profile = Student::factory()->create();
        $course = Course::factory()->create();
        Enrollment::factory()->create(['student_id' => $profile->id, 'course_id' => $course->id]);

        $this->actingAs($profile->user)
            ->post(route('enrollments.store'), ['course_id' => $course->id])
            ->assertSessionHasErrors('course_id');

        $this->assertSame(1, Enrollment::count());
    }

    public function test_a_student_only_sees_classes_they_have_not_taken(): void
    {
        $profile = Student::factory()->create();
        $taken = Course::factory()->create(['code' => 'TAKEN101', 'title' => 'Already Mine']);
        Course::factory()->create(['code' => 'OPEN101', 'title' => 'Still Open']);

        Enrollment::factory()->create(['student_id' => $profile->id, 'course_id' => $taken->id]);

        $this->actingAs($profile->user)
            ->get(route('enrollments.index'))
            ->assertOk()
            ->assertSee('Still Open')
            ->assertSee('Already Mine'); // listed under "my classes", not "available"
    }

    public function test_a_student_cannot_enroll_another_student(): void
    {
        $mine = Student::factory()->create();
        $theirs = Student::factory()->create();
        $course = Course::factory()->create();

        // student_id is never taken from the request, so the row can only
        // ever land on the signed-in student.
        $this->actingAs($mine->user)
            ->post(route('enrollments.store'), [
                'course_id' => $course->id,
                'student_id' => $theirs->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('enrollments', ['student_id' => $mine->id]);
        $this->assertDatabaseMissing('enrollments', ['student_id' => $theirs->id]);
    }

    // ------------------------------------------------------------------
    // Dropping — reason required, registrar decides
    // ------------------------------------------------------------------

    public function test_dropping_requires_a_reason(): void
    {
        $profile = Student::factory()->create();
        $enrollment = Enrollment::factory()->create(['student_id' => $profile->id]);

        $this->actingAs($profile->user)
            ->patch(route('enrollments.drop', $enrollment), ['drop_reason' => ''])
            ->assertSessionHasErrors('drop_reason');

        $this->actingAs($profile->user)
            ->patch(route('enrollments.drop', $enrollment), ['drop_reason' => 'too short'])
            ->assertSessionHasErrors('drop_reason');

        $this->assertTrue($enrollment->refresh()->isEnrolled());
    }

    public function test_a_drop_request_leaves_the_student_enrolled_until_review(): void
    {
        $profile = Student::factory()->create();
        $enrollment = Enrollment::factory()->create(['student_id' => $profile->id]);

        $this->actingAs($profile->user)
            ->patch(route('enrollments.drop', $enrollment), [
                'drop_reason' => 'The schedule clashes with my thesis defence.',
            ])
            ->assertRedirect(route('enrollments.index'));

        $enrollment->refresh();

        $this->assertTrue($enrollment->isDropPending());
        $this->assertSame('The schedule clashes with my thesis defence.', $enrollment->drop_reason);
        $this->assertNotNull($enrollment->drop_requested_at);

        // Still counts as active — nothing has been approved yet.
        $this->assertSame(1, $profile->activeEnrollments()->count());

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $profile->user->id,
            'action' => AuditLogger::DROP_REQUESTED,
        ]);
    }

    public function test_a_student_cannot_request_a_drop_for_someone_else(): void
    {
        $theirs = Enrollment::factory()->create();
        $me = Student::factory()->create();

        $this->actingAs($me->user)
            ->patch(route('enrollments.drop', $theirs), ['drop_reason' => 'Not my class at all.'])
            ->assertForbidden();

        $this->assertTrue($theirs->refresh()->isEnrolled());
    }

    public function test_a_student_cannot_approve_their_own_drop(): void
    {
        $profile = Student::factory()->create();
        $enrollment = Enrollment::factory()->dropPending()->create(['student_id' => $profile->id]);

        $this->actingAs($profile->user)
            ->patch(route('drop-requests.update', $enrollment), ['decision' => 'approve'])
            ->assertForbidden();

        $this->assertTrue($enrollment->refresh()->isDropPending());
    }

    // ------------------------------------------------------------------
    // Registrar review
    // ------------------------------------------------------------------

    public function test_approving_a_drop_removes_the_class_immediately(): void
    {
        $registrar = User::factory()->registrar()->create();
        $profile = Student::factory()->create();
        $enrollment = Enrollment::factory()->dropPending()->create(['student_id' => $profile->id]);

        $this->actingAs($registrar)
            ->patch(route('drop-requests.update', $enrollment), ['decision' => 'approve'])
            ->assertRedirect(route('drop-requests.index'));

        $enrollment->refresh();

        $this->assertTrue($enrollment->isDropped());
        $this->assertSame($registrar->id, $enrollment->reviewed_by);
        $this->assertNotNull($enrollment->reviewed_at);

        // Gone from the student's portal the moment it is approved.
        $this->assertSame(0, $profile->activeEnrollments()->count());

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $registrar->id,
            'action' => AuditLogger::DROP_APPROVED,
        ]);
    }

    public function test_declining_a_drop_puts_the_student_back_as_enrolled(): void
    {
        $registrar = User::factory()->registrar()->create();
        $enrollment = Enrollment::factory()->dropPending()->create();

        $this->actingAs($registrar)
            ->patch(route('drop-requests.update', $enrollment), [
                'decision' => 'decline',
                'review_note' => 'Too late in the term to drop.',
            ])
            ->assertRedirect();

        $enrollment->refresh();

        $this->assertTrue($enrollment->isEnrolled());
        $this->assertSame('Too late in the term to drop.', $enrollment->review_note);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $registrar->id,
            'action' => AuditLogger::DROP_DECLINED,
        ]);
    }

    public function test_a_settled_request_cannot_be_reviewed_again(): void
    {
        $registrar = User::factory()->registrar()->create();
        $enrollment = Enrollment::factory()->dropped()->create();

        $this->actingAs($registrar)
            ->patch(route('drop-requests.update', $enrollment), ['decision' => 'decline'])
            ->assertForbidden();

        $this->assertTrue($enrollment->refresh()->isDropped());
    }

    public function test_the_queue_shows_only_pending_requests_by_default(): void
    {
        $registrar = User::factory()->registrar()->create();

        $pending = Enrollment::factory()->dropPending()->create();
        $pending->course()->update(['code' => 'PENDING1']);

        $settled = Enrollment::factory()->dropped()->create();
        $settled->course()->update(['code' => 'SETTLED1']);

        $this->actingAs($registrar)
            ->get(route('drop-requests.index'))
            ->assertOk()
            ->assertSee('PENDING1')
            ->assertDontSee('SETTLED1');

        $this->actingAs($registrar)
            ->get(route('drop-requests.index', ['all' => 1]))
            ->assertOk()
            ->assertSee('PENDING1')
            ->assertSee('SETTLED1');
    }

    public function test_a_student_can_re_request_a_drop_after_being_declined(): void
    {
        $registrar = User::factory()->registrar()->create();
        $profile = Student::factory()->create();
        $enrollment = Enrollment::factory()->dropPending()->create(['student_id' => $profile->id]);

        $this->actingAs($registrar)
            ->patch(route('drop-requests.update', $enrollment), [
                'decision' => 'decline',
                'review_note' => 'Speak to your adviser first.',
            ]);

        $this->actingAs($profile->user)
            ->patch(route('enrollments.drop', $enrollment), [
                'drop_reason' => 'I have now spoken to my adviser and they agree.',
            ])
            ->assertRedirect();

        $enrollment->refresh();

        $this->assertTrue($enrollment->isDropPending());
        // The previous decision is cleared so the queue does not show a stale one.
        $this->assertNull($enrollment->reviewed_at);
        $this->assertNull($enrollment->review_note);
    }
}
