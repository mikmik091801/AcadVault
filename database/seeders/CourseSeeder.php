<?php

namespace Database\Seeders;

use App\Enums\Program;
use App\Enums\Role;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $faculty = User::where('email', 'faculty@acadvault.test')->first();

        // [code, title, assigned, program, year_level, semester]
        $courses = [
            ['CS101', 'Introduction to Computing', true, Program::ComputerScience, 1, 1],
            ['CS214', 'Data Structures and Algorithms', true, Program::ComputerScience, 2, 1],
            ['IT330', 'Information Assurance and Security', true, Program::InformationTechnology, 3, 1],
            ['MATH120', 'Discrete Mathematics', false, Program::ComputerScience, 1, 2],
            ['GE105', 'Ethics in the Digital Age', false, Program::MultimediaArts, 1, 2],
        ];

        foreach ($courses as [$code, $title, $assigned, $program, $year, $semester]) {
            Course::updateOrCreate(
                ['code' => $code],
                [
                    'title' => $title,
                    // Two courses are left unassigned so the "Unassigned"
                    // badge and the faculty scoping are both visible.
                    'faculty_id' => $assigned ? $faculty?->id : null,
                    'program' => $program->value,
                    'year_level' => $year,
                    'semester' => $semester,
                    'units' => 3,
                ],
            );
        }
    }
}
