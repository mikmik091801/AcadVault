<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The courses seeded before the curriculum fields existed were left with
 * no program/year placement, which meant any student could enroll in them.
 * Map each known course to its correct program, year level and semester.
 */
return new class extends Migration
{
    public function up(): void
    {
        $map = [
            'CS101' => ['program' => 'Bachelor of Science in Computer Science', 'year_level' => 1, 'semester' => 1, 'units' => 3],
            'CS214' => ['program' => 'Bachelor of Science in Computer Science', 'year_level' => 2, 'semester' => 1, 'units' => 3],
            'IT330' => ['program' => 'Bachelor of Science in Information Technology', 'year_level' => 3, 'semester' => 1, 'units' => 3],
            'MATH120' => ['program' => 'Bachelor of Science in Computer Science', 'year_level' => 1, 'semester' => 2, 'units' => 3],
            'GE105' => ['program' => 'Bachelor of Multimedia Arts', 'year_level' => 1, 'semester' => 2, 'units' => 3],
        ];

        foreach ($map as $code => $fields) {
            DB::table('courses')->where('code', $code)->update($fields);
        }
    }

    public function down(): void
    {
        DB::table('courses')->whereIn('code', array_keys([
            'CS101' => 1,
            'CS214' => 1,
            'IT330' => 1,
            'MATH120' => 1,
            'GE105' => 1,
        ]))->update(['program' => null, 'year_level' => null, 'semester' => null, 'units' => null]);
    }
};
