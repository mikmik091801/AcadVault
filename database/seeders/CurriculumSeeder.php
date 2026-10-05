<?php

namespace Database\Seeders;

use App\Enums\Program;
use App\Models\Course;
use Illuminate\Database\Seeder;

/**
 * Four-year curricula for the College of Computing Education's BSIT and BSCS.
 *
 * Built from the CHED sample curricula that every Philippine school works
 * from (CMO 25 s.2015). They are representative rather than a transcription of
 * University of Mindanao's own prospectus; swap the arrays below when the real
 * checklist is to hand and nothing else needs to change. The college's other
 * programs have no seeded curriculum — the registrar adds their subjects on
 * the Courses screen.
 *
 * Codes carry a program prefix because `courses.code` is unique across the
 * whole catalog, and both programs share subject titles.
 *
 * Each row is [code, title, year, semester, units].
 */
class CurriculumSeeder extends Seeder
{
    public function run(): void
    {
        $this->seed(Program::InformationTechnology, 'IT', $this->informationTechnology());
        $this->seed(Program::ComputerScience, 'CS', $this->computerScience());

        $this->command?->info('Seeded '.Course::whereNotNull('program')->count().' curriculum subjects.');
    }

    /**
     * @param  array<int, array{0: string, 1: string, 2: int, 3: int, 4: int}>  $subjects
     */
    private function seed(Program $program, string $prefix, array $subjects): void
    {
        foreach ($subjects as [$code, $title, $year, $semester, $units]) {
            Course::updateOrCreate(
                ['code' => "{$prefix}-{$code}"],
                [
                    'title' => $title,
                    'program' => $program->value,
                    'year_level' => $year,
                    'semester' => $semester,
                    'units' => $units,
                ],
            );
        }
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: int, 3: int, 4: int}>
     */
    private function informationTechnology(): array
    {
        return [
            // ---- First year ------------------------------------------------
            ['COMP1', 'Introduction to Computing', 1, 1, 3],
            ['PROG1', 'Computer Programming 1', 1, 1, 3],
            ['MATHPREP', 'Pre-Calculus', 1, 1, 3],
            ['GE1', 'Mathematics in the Modern World', 1, 1, 3],
            ['GE2', 'Understanding the Self', 1, 1, 3],
            ['FIL1', 'Komunikasyon sa Akademikong Filipino', 1, 1, 3],
            ['PE1', 'Physical Fitness', 1, 1, 2],
            ['NSTP1', 'National Service Training Program 1', 1, 1, 3],

            ['DISC1', 'Discrete Structures 1', 1, 2, 3],
            ['PROG2', 'Computer Programming 2', 1, 2, 3],
            ['WEB1', 'Web Development', 1, 2, 3],
            ['GE3', 'Readings in Philippine History', 1, 2, 3],
            ['GE4', 'Purposive Communication', 1, 2, 3],
            ['FIL2', 'Pagbasa at Pagsulat tungo sa Pananaliksik', 1, 2, 3],
            ['PE2', 'Rhythmic Activities', 1, 2, 2],
            ['NSTP2', 'National Service Training Program 2', 1, 2, 3],

            // ---- Second year -----------------------------------------------
            ['DSA1', 'Data Structures and Algorithms', 2, 1, 3],
            ['DIGI1', 'Digital Logic Design', 2, 1, 3],
            ['PT1', 'Platform Technologies', 2, 1, 3],
            ['ACCTG', 'Fundamentals of Accounting', 2, 1, 3],
            ['GE5', 'The Contemporary World', 2, 1, 3],
            ['GE6', 'Art Appreciation', 2, 1, 3],
            ['GEEL1', 'Living in the IT Era', 2, 1, 3],
            ['PE3', 'Individual and Dual Sports', 2, 1, 2],

            ['OOP1', 'Object Oriented Programming', 2, 2, 3],
            ['IM1', 'Information Management', 2, 2, 3],
            ['NET1', 'Data Communications and Networking 1', 2, 2, 3],
            ['STAT1', 'Statistics and Probability', 2, 2, 3],
            ['GE7', 'Science, Technology and Society', 2, 2, 3],
            ['GEEL2', 'Reading Visual Arts', 2, 2, 3],
            ['PE4', 'Team Sports and Games', 2, 2, 2],

            ['SP1', 'Social Issues and Professional Practice', 2, 3, 3],
            ['QM1', 'Quantitative Methods', 2, 3, 3],
            ['HCI1', 'Human Computer Interaction', 2, 3, 3],

            // ---- Third year ------------------------------------------------
            ['IM2', 'Information Management 2', 3, 1, 3],
            ['NET2', 'Data Communications and Networking 2', 3, 1, 3],
            ['APPDEV1', 'Applications Development 1', 3, 1, 3],
            ['SAD1', 'System Analysis and Design', 3, 1, 3],
            ['OS1', 'Operating Systems', 3, 1, 3],
            ['ITEL1', 'IT Elective 1', 3, 1, 3],
            ['ITPEL1', 'IT Professional Elective 1', 3, 1, 3],
            ['ITPEL2', 'IT Professional Elective 2', 3, 1, 3],

            ['IAS1', 'Information Assurance and Security 1', 3, 2, 3],
            ['SIA1', 'System Integration and Architecture 1', 3, 2, 3],
            ['DWDM1', 'Fundamentals of Data Warehousing and Data Mining', 3, 2, 3],
            ['RES1', 'Methods of Research in Computing', 3, 2, 3],
            ['TECHNO', 'Technopreneurship', 3, 2, 3],
            ['ITEL2', 'IT Elective 2', 3, 2, 3],
            ['ITPEL3', 'IT Professional Elective 3', 3, 2, 3],
            ['GE8', 'Ethics', 3, 2, 3],

            ['IPT1', 'Integrative Programming and Technologies', 3, 3, 3],
            ['ITEL3', 'IT Elective 3', 3, 3, 3],
            ['CAP1', 'Capstone Project 1', 3, 3, 3],

            // ---- Fourth year -----------------------------------------------
            ['SYSAD', 'Systems Administration and Maintenance', 4, 1, 3],
            ['CAP2', 'Capstone Project 2', 4, 1, 3],
            ['ITREV', 'Certification Exam Review', 4, 1, 3],
            ['RIZAL', 'Life, Works and Writings of Dr. Jose Rizal', 4, 1, 3],
            ['ITPEL4', 'IT Professional Elective 4', 4, 1, 3],

            ['PRAC', 'Practicum', 4, 2, 6],
            ['SEMTOUR', 'Seminars and Tours', 4, 2, 3],
        ];
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: int, 3: int, 4: int}>
     */
    private function computerScience(): array
    {
        return [
            // ---- First year ------------------------------------------------
            ['COMP1', 'Introduction to Computing', 1, 1, 3],
            ['PROG1', 'Computer Programming 1', 1, 1, 3],
            ['MATHPREP', 'Pre-Calculus', 1, 1, 3],
            ['GE1', 'Mathematics in the Modern World', 1, 1, 3],
            ['GE2', 'Understanding the Self', 1, 1, 3],
            ['FIL1', 'Komunikasyon sa Akademikong Filipino', 1, 1, 3],
            ['PE1', 'Physical Fitness', 1, 1, 2],
            ['NSTP1', 'National Service Training Program 1', 1, 1, 3],

            ['DISC1', 'Discrete Structures 1', 1, 2, 3],
            ['PROG2', 'Computer Programming 2', 1, 2, 3],
            ['WEB1', 'Web Development', 1, 2, 3],
            ['GE3', 'Readings in Philippine History', 1, 2, 3],
            ['GE4', 'Purposive Communication', 1, 2, 3],
            ['FIL2', 'Pagbasa at Pagsulat tungo sa Pananaliksik', 1, 2, 3],
            ['PE2', 'Rhythmic Activities', 1, 2, 2],
            ['NSTP2', 'National Service Training Program 2', 1, 2, 3],

            // ---- Second year -----------------------------------------------
            ['DISC2', 'Discrete Structures 2', 2, 1, 3],
            ['DSA1', 'Data Structures and Algorithms', 2, 1, 3],
            ['DIGI1', 'Digital Logic Design', 2, 1, 3],
            ['ACCTG', 'Fundamentals of Accounting', 2, 1, 3],
            ['GE5', 'The Contemporary World', 2, 1, 3],
            ['GE6', 'Art Appreciation', 2, 1, 3],
            ['GEEL1', 'Living in the IT Era', 2, 1, 3],
            ['PE3', 'Individual and Dual Sports', 2, 1, 2],

            ['ALGO1', 'Analysis and Design of Algorithms', 2, 2, 3],
            ['OOP1', 'Object Oriented Programming', 2, 2, 3],
            ['IM1', 'Information Management', 2, 2, 3],
            ['CORG1', 'Computer Organization and Architecture', 2, 2, 3],
            ['STAT1', 'Statistics and Probability', 2, 2, 3],
            ['GE7', 'Science, Technology and Society', 2, 2, 3],
            ['GEEL2', 'Reading Visual Arts', 2, 2, 3],
            ['PE4', 'Team Sports and Games', 2, 2, 2],

            ['QM1', 'Quantitative Methods', 2, 3, 3],
            ['SP1', 'Social Issues and Professional Practice', 2, 3, 3],
            ['HCI1', 'Human Computer Interaction', 2, 3, 3],

            // ---- Third year ------------------------------------------------
            ['AUTO1', 'Automata Theory and Formal Languages', 3, 1, 3],
            ['NET1', 'Data Communications and Networking', 3, 1, 3],
            ['SOFTENG1', 'Software Engineering 1', 3, 1, 3],
            ['APPDEV1', 'Applications Development 1', 3, 1, 3],
            ['OS1', 'Operating Systems', 3, 1, 3],
            ['CSEL1', 'Intelligent Systems', 3, 1, 3],
            ['CSPEL1', 'CS Professional Elective 1', 3, 1, 3],
            ['CSPEL2', 'CS Professional Elective 2', 3, 1, 3],

            ['PL1', 'Programming Languages', 3, 2, 3],
            ['SOFTENG2', 'Software Engineering 2', 3, 2, 3],
            ['CSEL2', 'Graphics and Visual Computing', 3, 2, 3],
            ['APPDEV2', 'Applications Development 2', 3, 2, 3],
            ['RES1', 'Methods of Research in Computing', 3, 2, 3],
            ['CSPEL3', 'CS Professional Elective 3', 3, 2, 3],
            ['TECHNO', 'Technopreneurship', 3, 2, 3],
            ['GE8', 'Ethics', 3, 2, 3],

            ['PRAC1', 'Practicum', 3, 3, 3],

            // ---- Fourth year -----------------------------------------------
            ['CSEL3', 'Natural Language Processing', 4, 1, 3],
            ['THESIS1', 'Thesis Writing 1', 4, 1, 3],
            ['CSREV', 'Certification Exam Review', 4, 1, 3],
            ['RIZAL', 'Life, Works and Writings of Dr. Jose Rizal', 4, 1, 3],
            ['CSPEL4', 'CS Professional Elective 4', 4, 1, 3],

            ['IAS1', 'Information Assurance and Security', 4, 2, 3],
            ['THESIS2', 'Thesis Writing 2', 4, 2, 3],
            ['SEMTOUR', 'Seminars and Tours', 4, 2, 3],
        ];
    }
}
