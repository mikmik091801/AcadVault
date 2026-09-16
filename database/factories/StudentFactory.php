<?php

namespace Database\Factories;

use App\Enums\Program;
use App\Enums\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->student(),
            'student_number' => date('Y').'-'.fake()->unique()->numberBetween(10000, 99999),
            'program' => fake()->randomElement(Program::values()),
            'year_level' => fake()->numberBetween(1, 4),
        ];
    }

    /**
     * Attach the profile to an existing user, promoting them to student.
     */
    public function forUser(User $user): static
    {
        $user->forceFill(['role' => Role::Student])->save();

        return $this->state(fn (array $attributes) => ['user_id' => $user->id]);
    }
}
