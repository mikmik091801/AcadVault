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

    public function unassigned(): static
    {
        return $this->state(fn (array $attributes) => ['faculty_id' => null]);
    }
}
