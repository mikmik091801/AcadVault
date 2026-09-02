<?php

namespace Database\Factories;

use App\Models\AcademicRecord;
use App\Models\Export;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Export>
 */
class ExportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'academic_record_id' => AcademicRecord::factory(),
            'exported_by' => User::factory()->registrar(),
            'file_hash' => hash('sha256', fake()->uuid()),
            'qr_code_path' => null,
        ];
    }
}
