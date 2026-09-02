<?php

namespace Database\Factories;

use App\Models\AcademicRecord;
use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicRecord>
 */
class AcademicRecordFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $grade = fake()->randomElement(['1.00', '1.25', '1.50', '1.75', '2.00', '2.25', '2.50', '2.75', '3.00', '5.00']);

        return [
            'student_id' => Student::factory(),
            'course_id' => Course::factory(),
            'grade' => $grade,
            'remarks' => $grade === '5.00' ? 'Failed' : 'Passed',
            'created_by' => User::factory()->registrar(),
        ];
    }
}
