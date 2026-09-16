<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Note: model events are deliberately NOT muted here. Letting them fire
     * means AcademicRecordObserver writes real audit entries as the sample
     * records are created, so the audit log screen has content to show
     * immediately after seeding.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CourseSeeder::class,
            CurriculumSeeder::class,
            AcademicRecordSeeder::class,
            ExportSeeder::class,
        ]);

        $this->command?->newLine();
        $this->command?->info('AcadVault demo accounts (password: "password"):');
        $this->command?->table(
            ['Role', 'Email'],
            [
                ['Admin', 'admin@acadvault.test'],
                ['Registrar', 'registrar@acadvault.test'],
                ['Faculty', 'faculty@acadvault.test'],
                ['Student', 'student@acadvault.test'],
            ],
        );
    }
}
