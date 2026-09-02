<?php

namespace Tests\Feature;

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Faculty seeing who is in their class.
 *
 * The roster comes from enrolments, not from filed grades — an instructor
 * needs the class list before anyone has a grade.
 */
class CourseRosterTest extends TestCase
{
    use RefreshDatabase;

    private function enrol(Course $course, string $name, string $number): Student
    {
        $student = Student::factory()->create([
            'student_number' => $number,
            'user_id' => User::factory()->student()->create(['name' => $name])->id,
        ]);

        Enrollment::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);

        return $student;
    }

    public function test_faculty_see_the_students_enrolled_in_their_own_class(): void
    {
        $faculty = User::factory()->faculty()->create();
        $course = Course::factory()->create(['faculty_id' => $faculty->id, 'code' => 'CS101']);

        $this->enrol($course, 'Juan Dela Cruz', '2026-00001');
        $this->enrol($course, 'Ana Bautista', '2026-00002');

        $this->actingAs($faculty)
            ->get(route('courses.show', $course))
            ->assertOk()
            ->assertSee('Class list')
            ->assertSee('Juan Dela Cruz')
            ->assertSee('2026-00001')
            ->assertSee('Ana Bautista')
            ->assertSee('2026-00002');
    }

    public function test_faculty_cannot_see_the_roster_of_a_class_they_do_not_teach(): void
    {
        $faculty = User::factory()->faculty()->create();
        $someoneElses = Course::factory()->create();

        $this->enrol($someoneElses, 'Not Their Student', '2026-09999');

        $this->actingAs($faculty)
            ->get(route('courses.show', $someoneElses))
            ->assertForbidden();
    }

    public function test_a_student_who_dropped_is_off_the_roster(): void
    {
        $faculty = User::factory()->faculty()->create();
        $course = Course::factory()->create(['faculty_id' => $faculty->id]);

        $staying = $this->enrol($course, 'Still Here', '2026-00001');
        $leaving = $this->enrol($course, 'Long Gone', '2026-00002');

        $leaving->enrollments()->update(['status' => EnrollmentStatus::Dropped]);

        $this->actingAs($faculty)
            ->get(route('courses.show', $course))
            ->assertOk()
            ->assertSee('Still Here')
            ->assertDontSee('Long Gone');

        $this->assertSame(1, $course->activeEnrollments()->count());
        $this->assertNotNull($staying);
    }

    public function test_a_student_awaiting_a_drop_decision_is_still_on_the_roster(): void
    {
        $faculty = User::factory()->faculty()->create();
        $course = Course::factory()->create(['faculty_id' => $faculty->id]);

        $student = $this->enrol($course, 'Maybe Leaving', '2026-00003');
        $student->enrollments()->update([
            'status' => EnrollmentStatus::DropPending,
            'drop_reason' => 'Clashes with my thesis defence.',
            'drop_requested_at' => now(),
        ]);

        // They have not left until the registrar approves it, so the
        // instructor should still see them — flagged.
        $this->actingAs($faculty)
            ->get(route('courses.show', $course))
            ->assertOk()
            ->assertSee('Maybe Leaving')
            ->assertSee('Drop pending');
    }

    public function test_the_course_list_counts_enrolled_students_per_class(): void
    {
        $faculty = User::factory()->faculty()->create();
        $course = Course::factory()->create(['faculty_id' => $faculty->id, 'code' => 'CS101']);

        $this->enrol($course, 'One Student', '2026-00001');
        $this->enrol($course, 'Two Student', '2026-00002');

        $this->actingAs($faculty)
            ->get(route('courses.index'))
            ->assertOk()
            ->assertSee('Students');

        $this->assertSame(2, Course::withCount('activeEnrollments')->find($course->id)->active_enrollments_count);
    }

    public function test_the_faculty_dashboard_leads_with_their_classes_and_headcount(): void
    {
        $faculty = User::factory()->faculty()->create();
        $course = Course::factory()->create(['faculty_id' => $faculty->id, 'code' => 'MINE101']);
        $other = Course::factory()->create(['code' => 'THEIRS101']);

        $this->enrol($course, 'My Student', '2026-00001');
        $this->enrol($other, 'Their Student', '2026-00002');

        // The dashboard lists classes and headcounts, not individual names —
        // the names live on the course page.
        $this->actingAs($faculty)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('MINE101')
            ->assertSee('Students enrolled')
            ->assertDontSee('THEIRS101')
            ->assertDontSee('Their Student');
    }

    public function test_a_student_taking_two_classes_is_counted_once_on_the_dashboard(): void
    {
        $faculty = User::factory()->faculty()->create();
        $first = Course::factory()->create(['faculty_id' => $faculty->id]);
        $second = Course::factory()->create(['faculty_id' => $faculty->id]);

        $student = $this->enrol($first, 'Busy Student', '2026-00001');
        Enrollment::factory()->create(['student_id' => $student->id, 'course_id' => $second->id]);

        $response = $this->actingAs($faculty)->get(route('dashboard'))->assertOk();

        // Two enrolments, one human.
        $this->assertSame(1, Enrollment::whereIn('course_id', [$first->id, $second->id])
            ->active()
            ->distinct('student_id')
            ->count('student_id'));

        $response->assertSee('Students enrolled');
    }
}
