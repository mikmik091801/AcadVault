<?php

namespace Tests\Feature;

use App\Models\AcademicRecord;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * A grade belongs to a class the student is actually taking, and each
 * student has at most one grade per course.
 */
class GradeEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    private User $faculty;

    private Course $course;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->faculty = User::factory()->faculty()->create();
        $this->course = Course::factory()->create(['faculty_id' => $this->faculty->id]);
        $this->student = Student::factory()->create();
    }

    private function fileGrade(array $overrides = []): TestResponse
    {
        return $this->actingAs($this->faculty)->post(route('records.store'), [
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'grade' => '1.50',
            'remarks' => 'Passed',
            ...$overrides,
        ]);
    }

    public function test_a_student_who_is_not_enrolled_cannot_be_graded(): void
    {
        $this->fileGrade()->assertSessionHasErrors([
            'student_id' => 'This student is not enrolled in the selected course.',
        ]);

        $this->assertDatabaseCount('academic_records', 0);
    }

    public function test_a_student_who_has_dropped_the_class_cannot_be_graded(): void
    {
        Enrollment::factory()->dropped()->create([
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
        ]);

        $this->fileGrade()->assertSessionHasErrors('student_id');

        $this->assertDatabaseCount('academic_records', 0);
    }

    public function test_a_second_grade_for_the_same_student_and_course_is_rejected(): void
    {
        Enrollment::factory()->create([
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
        ]);

        $this->fileGrade()->assertSessionHasNoErrors();
        $this->fileGrade(['grade' => '1.00'])->assertSessionHasErrors('student_id');

        $this->assertDatabaseCount('academic_records', 1);
    }

    public function test_an_older_grade_for_a_student_no_longer_enrolled_can_still_be_corrected(): void
    {
        // Filed before enrolment existed: there is no enrolment row at all.
        $record = AcademicRecord::factory()->create([
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
        ]);

        $this->actingAs($this->faculty)
            ->put(route('records.update', $record), [
                'student_id' => $this->student->id,
                'course_id' => $this->course->id,
                'grade' => '2.00',
                'remarks' => 'Corrected',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('2.00', $record->fresh()->grade);
    }

    public function test_grading_from_the_class_list_returns_to_the_class_list(): void
    {
        Enrollment::factory()->create([
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
        ]);

        $this->fileGrade(['return_to_course' => 1])
            ->assertRedirect(route('courses.show', $this->course));
    }

    public function test_the_class_list_offers_grade_entry_only_for_ungraded_students(): void
    {
        $graded = Student::factory()->create();

        foreach ([$this->student, $graded] as $student) {
            Enrollment::factory()->create(['student_id' => $student->id, 'course_id' => $this->course->id]);
        }

        AcademicRecord::factory()->create([
            'student_id' => $graded->id,
            'course_id' => $this->course->id,
            'grade' => '1.75',
        ]);

        $this->actingAs($this->faculty)
            ->get(route('courses.show', $this->course))
            ->assertOk()
            ->assertSee(route('records.create', [
                'course_id' => $this->course->id,
                'student_id' => $this->student->id,
                'return_to_course' => 1,
            ]))
            ->assertDontSee(route('records.create', [
                'course_id' => $this->course->id,
                'student_id' => $graded->id,
                'return_to_course' => 1,
            ]))
            ->assertSee('1.75');
    }
}
