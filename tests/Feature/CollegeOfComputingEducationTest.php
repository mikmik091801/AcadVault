<?php

namespace Tests\Feature;

use App\Enums\Program;
use App\Models\AcademicRecord;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AcadVault serves one college: the University of Mindanao's College of
 * Computing Education.
 */
class CollegeOfComputingEducationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_course_catalogue_can_be_filtered_by_program(): void
    {
        Course::factory()->create(['code' => 'CS-ONLY', 'program' => Program::ComputerScience->value]);
        Course::factory()->create(['code' => 'IT-ONLY', 'program' => Program::InformationTechnology->value]);

        $this->actingAs(User::factory()->registrar()->create())
            ->get(route('courses.index', ['program' => 'BSCS']))
            ->assertOk()
            ->assertSee('CS-ONLY')
            ->assertDontSee('IT-ONLY');
    }

    public function test_the_student_list_shows_each_students_record_count(): void
    {
        $student = Student::factory()->create();
        AcademicRecord::factory()->count(3)->create(['student_id' => $student->id]);

        $this->actingAs(User::factory()->registrar()->create())
            ->get(route('students.index'))
            ->assertOk()
            ->assertViewHas('students', fn ($students) => $students->first()->academic_records_count === 3);
    }

    public function test_the_cleanup_removes_other_colleges_subjects_but_keeps_any_with_history(): void
    {
        $computing = Course::factory()->create(['program' => Program::ComputerScience->value]);
        $openToAll = Course::factory()->create(['program' => null]);
        $unused = Course::factory()->create(['program' => 'Bachelor of Science in Computer Engineering']);
        $enrolledIn = Course::factory()->create(['program' => 'Bachelor of Science in Computer Engineering']);
        $graded = Course::factory()->create(['program' => 'Bachelor of Science in Nursing']);

        Enrollment::factory()->create(['course_id' => $enrolledIn->id]);
        AcademicRecord::factory()->create(['course_id' => $graded->id]);

        $migration = require database_path('migrations/2026_09_29_121529_remove_courses_outside_the_college_of_computing_education.php');
        $migration->up();

        $this->assertModelMissing($unused);
        $this->assertModelExists($computing);
        $this->assertModelExists($openToAll);
        $this->assertModelExists($enrolledIn);
        $this->assertModelExists($graded);
    }
}
