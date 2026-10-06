<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The admin search must be case-insensitive (Render runs PostgreSQL, where
 * LIKE is case-sensitive) and match on any name part, not just the exact
 * full name.
 */
class StudentSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_searching_a_first_name_matches_every_student_with_that_name(): void
    {
        Student::factory()->forUser(User::factory()->student()->create(['name' => 'Anna L. Bautista', 'first_name' => 'Anna', 'last_name' => 'Bautista']))->create();
        Student::factory()->forUser(User::factory()->student()->create(['name' => 'Anna Mae Reyes', 'first_name' => 'Anna', 'last_name' => 'Reyes']))->create();
        Student::factory()->forUser(User::factory()->student()->create(['name' => 'Juan P. Dela Cruz', 'first_name' => 'Juan', 'last_name' => 'Dela Cruz']))->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('students.index', ['search' => 'anna']))
            ->assertOk()
            ->assertSee('Anna L. Bautista')
            ->assertSee('Anna Mae Reyes')
            ->assertDontSee('Juan P. Dela Cruz');
    }

    public function test_search_is_case_insensitive(): void
    {
        Student::factory()->forUser(User::factory()->student()->create(['name' => 'Anna L. Bautista']))->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('students.index', ['search' => 'ANNA']))
            ->assertOk()
            ->assertSee('Anna L. Bautista');
    }

    public function test_each_word_of_a_multi_word_search_must_appear_somewhere(): void
    {
        Student::factory()->forUser(User::factory()->student()->create(['name' => 'Anna L. Bautista']))->create();
        Student::factory()->forUser(User::factory()->student()->create(['name' => 'Anna Santos']))->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('students.index', ['search' => 'anna bautista']))
            ->assertOk()
            ->assertSee('Anna L. Bautista')
            ->assertDontSee('Anna Santos');
    }
}
