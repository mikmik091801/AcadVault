<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * AcadVault now serves only the College of Computing Education, so curriculum
 * subjects placed in any other college's program (the BSCpE curriculum seeded
 * earlier) are removed.
 *
 * A subject anybody has enrolled in or been graded for is kept, so no student
 * history is lost. Subjects with no program ("open to all") are untouched.
 *
 * The program names are written out rather than read from App\Enums\Program so
 * this migration keeps meaning the same thing if that list changes later.
 */
return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $computingPrograms = [
        'Bachelor of Science in Computer Science',
        'Bachelor of Science in Entertainment and Multimedia Computing Major in Game Development',
        'Bachelor of Science in Information Technology',
        'Bachelor of Library and Information Science',
        'Bachelor of Multimedia Arts',
    ];

    public function up(): void
    {
        DB::table('courses')
            ->whereNotNull('program')
            ->whereNotIn('program', $this->computingPrograms)
            ->whereNotExists(fn (Builder $query) => $query->selectRaw('1')
                ->from('enrollments')
                ->whereColumn('enrollments.course_id', 'courses.id'))
            ->whereNotExists(fn (Builder $query) => $query->selectRaw('1')
                ->from('academic_records')
                ->whereColumn('academic_records.course_id', 'courses.id'))
            ->delete();
    }

    /**
     * Deleted subjects are not restored; they were seed data with no history.
     */
    public function down(): void
    {
        //
    }
};
