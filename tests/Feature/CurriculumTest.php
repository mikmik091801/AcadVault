<?php

namespace Tests\Feature;

use App\Enums\Program;
use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A student is offered the subjects in their own program and year level, not
 * the whole university catalog.
 */
class CurriculumTest extends TestCase
{
    use RefreshDatabase;

    private function subject(string $code, string $title, ?Program $program, ?int $year, ?int $semester = 1): Course
    {
        return Course::factory()->create([
            'code' => $code,
            'title' => $title,
            'program' => $program?->value,
            'year_level' => $year,
            'semester' => $semester,
        ]);
    }

    public function test_a_first_year_is_offered_their_own_program_and_year(): void
    {
        $student = Student::factory()->create([
            'program' => Program::ComputerScience->value,
            'year_level' => 1,
        ]);

        $this->subject('CS111', 'Introduction to Computing', Program::ComputerScience, 1);
        $this->subject('CS211', 'Data Structures', Program::ComputerScience, 2);
        $this->subject('IT111', 'IT Fundamentals', Program::InformationTechnology, 1);

        $this->actingAs($student->user)
            ->get(route('enrollments.index'))
            ->assertOk()
            ->assertSee('Introduction to Computing')
            ->assertDontSee('Data Structures')   // right program, wrong year
            ->assertDontSee('IT Fundamentals');  // right year, wrong program
    }

    public function test_the_offer_follows_the_student_up_a_year(): void
    {
        $student = Student::factory()->create([
            'program' => Program::ComputerScience->value,
            'year_level' => 2,
        ]);

        $this->subject('CS111', 'Introduction to Computing', Program::ComputerScience, 1);
        $this->subject('CS211', 'Data Structures', Program::ComputerScience, 2);

        $this->actingAs($student->user)
            ->get(route('enrollments.index'))
            ->assertOk()
            ->assertSee('Data Structures')
            ->assertDontSee('Introduction to Computing');
    }

    public function test_an_unplaced_subject_stays_open_to_everyone(): void
    {
        $student = Student::factory()->create([
            'program' => Program::MultimediaArts->value,
            'year_level' => 3,
        ]);

        // Subjects seeded before the curriculum existed carry no placement.
        $this->subject('GE101', 'Purposive Communication', null, null, null);

        $this->actingAs($student->user)
            ->get(route('enrollments.index'))
            ->assertOk()
            ->assertSee('Purposive Communication');
    }

    public function test_subjects_are_grouped_by_semester(): void
    {
        $student = Student::factory()->create([
            'program' => Program::InformationTechnology->value,
            'year_level' => 1,
        ]);

        $this->subject('IT111', 'IT Fundamentals', Program::InformationTechnology, 1, 1);
        $this->subject('IT112', 'Web Systems', Program::InformationTechnology, 1, 2);

        $this->actingAs($student->user)
            ->get(route('enrollments.index'))
            ->assertOk()
            ->assertSee('1st Semester')
            ->assertSee('2nd Semester');
    }

    public function test_a_program_with_no_curriculum_says_so_rather_than_looking_finished(): void
    {
        $student = Student::factory()->create([
            'program' => Program::MultimediaArts->value,
            'year_level' => 1,
        ]);

        // Somebody else's program has subjects; theirs has none.
        $this->subject('IT111', 'IT Fundamentals', Program::InformationTechnology, 1);

        $this->actingAs($student->user)
            ->get(route('enrollments.index'))
            ->assertOk()
            ->assertSee('No curriculum for your program yet')
            ->assertDontSee('Nothing left to enroll in');
    }

    public function test_a_registrar_can_place_a_subject_in_a_curriculum(): void
    {
        $this->actingAs(User::factory()->registrar()->create())
            ->post(route('courses.store'), [
                'code' => 'CS111',
                'title' => 'Introduction to Computing',
                'faculty_id' => null,
                'program' => Program::ComputerScience->value,
                'year_level' => 1,
                'semester' => 1,
                'units' => 3,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('courses', [
            'code' => 'CS111',
            'program' => Program::ComputerScience->value,
            'year_level' => 1,
            'semester' => 1,
            'units' => 3,
        ]);
    }

    public function test_a_subject_cannot_be_placed_in_a_program_that_does_not_exist(): void
    {
        $this->actingAs(User::factory()->registrar()->create())
            ->post(route('courses.store'), [
                'code' => 'ZZ999',
                'title' => 'Nonsense',
                'program' => 'BS Underwater Basket Weaving',
                'year_level' => 1,
                'semester' => 1,
                'units' => 3,
            ])
            ->assertSessionHasErrors('program');
    }
}
