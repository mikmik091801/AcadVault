<?php

namespace Tests\Feature;

use App\Enums\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The program field is a picker over the College of Computing Education's
 * programs rather than free text, so the stored value is always a real program.
 */
class StudentProgramTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'user_id' => User::factory()->create()->id,
            'student_number' => '2026-04521',
            'program' => Program::ComputerScience->value,
            'year_level' => 2,
        ], $overrides);
    }

    public function test_the_form_offers_only_the_college_of_computing_education_programs(): void
    {
        $this->actingAs(User::factory()->registrar()->create())
            ->get(route('students.create'))
            ->assertOk()
            ->assertSee('Bachelor of Science in Computer Science')
            ->assertSee('Bachelor of Multimedia Arts')
            ->assertSee('College of Computing Education')
            ->assertDontSee('Bachelor of Science in Nursing')
            ->assertDontSee('College of Engineering Education');
    }

    public function test_a_program_from_another_college_is_rejected(): void
    {
        $this->actingAs(User::factory()->registrar()->create())
            ->post(route('students.store'), $this->payload([
                'program' => 'Bachelor of Science in Computer Engineering',
            ]))
            ->assertSessionHasErrors('program');

        $this->assertDatabaseCount('students', 0);
    }

    public function test_a_program_from_the_catalog_is_accepted(): void
    {
        $this->actingAs(User::factory()->registrar()->create())
            ->post(route('students.store'), $this->payload([
                'program' => Program::GameDevelopment->value,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('students', [
            'student_number' => '2026-04521',
            'program' => Program::GameDevelopment->value,
        ]);
    }

    public function test_a_program_outside_the_catalog_is_rejected(): void
    {
        $this->actingAs(User::factory()->registrar()->create())
            ->post(route('students.store'), $this->payload([
                'program' => 'BS Underwater Basket Weaving',
            ]))
            ->assertSessionHasErrors('program');

        $this->assertDatabaseCount('students', 0);
    }

    public function test_a_profile_holding_a_retired_program_can_still_be_saved(): void
    {
        // Seeded before the catalog was fixed; editing the year level must not
        // force the registrar to also change the program.
        $student = Student::factory()->create(['program' => 'BS Information Systems']);

        $this->actingAs(User::factory()->registrar()->create())
            ->put(route('students.update', $student), [
                'user_id' => $student->user_id,
                'student_number' => $student->student_number,
                'program' => 'BS Information Systems',
                'year_level' => 4,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(4, $student->refresh()->year_level);
        $this->assertSame('BS Information Systems', $student->program);
    }

    public function test_a_retired_program_cannot_be_used_on_a_new_profile(): void
    {
        $this->actingAs(User::factory()->registrar()->create())
            ->post(route('students.store'), $this->payload([
                'program' => 'BS Information Systems',
            ]))
            ->assertSessionHasErrors('program');
    }

    public function test_every_program_belongs_to_a_college(): void
    {
        $grouped = Program::groupedByCollege();

        $this->assertSame(
            count(Program::cases()),
            collect($grouped)->flatten()->count(),
            'Every case must appear exactly once in the grouped list.'
        );

        $this->assertSame([Program::COLLEGE], array_keys($grouped));
    }
}
