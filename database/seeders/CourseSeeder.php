<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $faculty = User::where('email', 'faculty@acadvault.test')->first();

        $courses = [
            ['CS101', 'Introduction to Computing', true],
            ['CS214', 'Data Structures and Algorithms', true],
            ['IT330', 'Information Assurance and Security', true],
            ['MATH120', 'Discrete Mathematics', false],
            ['GE105', 'Ethics in the Digital Age', false],
        ];

        foreach ($courses as [$code, $title, $assigned]) {
            Course::updateOrCreate(
                ['code' => $code],
                [
                    'title' => $title,
                    // Two courses are left unassigned so the "Unassigned"
                    // badge and the faculty scoping are both visible.
                    'faculty_id' => $assigned ? $faculty?->id : null,
                ],
            );
        }
    }
}
