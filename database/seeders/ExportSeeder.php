<?php

namespace Database\Seeders;

use App\Models\AcademicRecord;
use App\Models\Export;
use App\Models\User;
use App\Support\ExportService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class ExportSeeder extends Seeder
{
    /**
     * Issues two sample documents so the exports screen and the public
     * /verify page can be exercised straight after seeding.
     */
    public function __construct(private ExportService $exports) {}

    public function run(): void
    {
        $registrar = User::where('email', 'registrar@acadvault.test')->first();

        if (! $registrar || Export::exists()) {
            return; // already seeded
        }

        $records = AcademicRecord::query()
            ->whereHas('student', fn ($s) => $s->where('student_number', '2026-01001'))
            ->with(['student.user', 'course'])
            ->limit(2)
            ->get();

        Auth::login($registrar);

        try {
            foreach ($records as $record) {
                $this->exports->issue($record, $registrar);
            }
        } finally {
            Auth::logout();
        }
    }
}
