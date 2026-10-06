<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('??###'),
            'title' => fake()->sentence(3),
            'faculty_id' => User::factory()->faculty(),
        ];
    }

    /**
     * Map the course to the given student's program and year, so it is
     * enrollable by them under the offeredTo() scope.
     */
    public function forStudent(\App\Models\Student $student): static
    {
        return $this->state(fn (array $attributes) => [
            'program' => $student->program,
            'year_level' => $student->year_level,
        ]);
    }

    public function unassigned(): static
    {
        return $this->state(fn (array $attributes) => ['faculty_id' => null]);
    }
}
