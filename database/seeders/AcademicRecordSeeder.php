<?php

namespace Database\Seeders;

use App\Models\AcademicRecord;
use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class AcademicRecordSeeder extends Seeder
{
    /**
     * Grades are fixed rather than random so that seeded data is reproducible
     * and tests can rely on it.
     *
     * The registrar is signed in for the duration of the seed so that
     * `created_by` and the audit entries written by AcademicRecordObserver are
     * attributed to a real person instead of appearing as "Unauthenticated".
     */
    public function run(): void
    {
        $registrar = User::where('email', 'registrar@acadvault.test')->first();

        if (! $registrar) {
            return;
        }

        Auth::login($registrar);

        try {
            $this->seedRecords($registrar);
        } finally {
            Auth::logout();
        }
    }

    private function seedRecords(User $registrar): void
    {
        // student number => [course code => [grade, remarks]]
        $grid = [
            '2026-01001' => [
                'CS101' => ['1.25', 'Passed'],
                'CS214' => ['1.50', 'Passed'],
                'IT330' => ['1.00', 'Passed with distinction'],
                'MATH120' => ['1.75', 'Passed'],
            ],
            '2026-01002' => [
                'CS101' => ['2.00', 'Passed'],
                'CS214' => ['2.25', 'Passed'],
                'IT330' => ['1.75', 'Passed'],
            ],
            '2026-01003' => [
                'CS101' => ['1.00', 'Passed with distinction'],
                'CS214' => ['1.25', 'Passed'],
                'IT330' => ['2.50', 'Passed'],
                'GE105' => ['1.50', 'Passed'],
            ],
            '2026-01004' => [
                'CS101' => ['2.75', 'Passed'],
                'CS214' => ['5.00', 'Failed — must retake'],
                'MATH120' => ['3.00', 'Passed'],
            ],
            '2026-01005' => [
                'CS101' => ['1.50', 'Passed'],
                'IT330' => ['1.25', 'Passed'],
                'GE105' => ['2.00', 'Passed'],
            ],
        ];

        $students = Student::pluck('id', 'student_number');
        $courses = Course::pluck('id', 'code');

        foreach ($grid as $studentNumber => $entries) {
            $studentId = $students[$studentNumber] ?? null;

            if (! $studentId) {
                continue;
            }

            foreach ($entries as $courseCode => [$grade, $remarks]) {
                $courseId = $courses[$courseCode] ?? null;

                if (! $courseId) {
                    continue;
                }

                // Matched on student + course only: grade and remarks are
                // encrypted, so they cannot be used in a WHERE clause.
                AcademicRecord::updateOrCreate(
                    ['student_id' => $studentId, 'course_id' => $courseId],
                    ['grade' => $grade, 'remarks' => $remarks, 'created_by' => $registrar->id],
                );
            }
        }
    }
}
