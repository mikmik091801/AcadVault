<?php

namespace Database\Factories;

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'course_id' => Course::factory(),
            'status' => EnrollmentStatus::Enrolled,
        ];
    }

    public function dropPending(string $reason = 'Timetable clash with my thesis defence.'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EnrollmentStatus::DropPending,
            'drop_reason' => $reason,
            'drop_requested_at' => now(),
        ]);
    }

    public function dropped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EnrollmentStatus::Dropped,
            'drop_reason' => 'Switched to a different elective.',
            'drop_requested_at' => now()->subDay(),
            'reviewed_at' => now(),
        ]);
    }
}
