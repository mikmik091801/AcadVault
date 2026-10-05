<?php

namespace Tests\Feature;

use App\Models\AcademicRecord;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_greeting_uses_the_whole_first_name_not_just_its_first_word(): void
    {
        $faculty = User::factory()->faculty()->create([
            'first_name' => 'Dr. Ramon',
            'last_name' => 'Cruz',
        ]);

        $this->actingAs($faculty)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Welcome back, Dr. Ramon');
    }

    public function test_faculty_see_how_many_enrolled_students_still_need_a_grade(): void
    {
        $faculty = User::factory()->faculty()->create();
        $course = Course::factory()->create(['faculty_id' => $faculty->id]);

        $graded = Enrollment::factory()->create(['course_id' => $course->id]);
        Enrollment::factory()->count(2)->create(['course_id' => $course->id]);
        Enrollment::factory()->dropped()->create(['course_id' => $course->id]);

        AcademicRecord::factory()->create([
            'student_id' => $graded->student_id,
            'course_id' => $course->id,
        ]);

        $this->actingAs($faculty)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['Grades to file', '2'])
            ->assertSee('2 to grade');
    }
}
