<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Places each subject in a curriculum so the enrollment portal can offer a
 * student the subjects for their own program and year rather than the whole
 * catalog.
 *
 * Every column is nullable: a subject that has not been mapped to a curriculum
 * yet stays open to everyone, which keeps the subjects seeded before this
 * migration usable instead of stranding them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('program')->nullable()->after('title');
            $table->unsignedTinyInteger('year_level')->nullable()->after('program');
            $table->unsignedTinyInteger('semester')->nullable()->after('year_level');
            $table->unsignedTinyInteger('units')->default(3)->after('semester');

            // The portal always filters on these three together.
            $table->index(['program', 'year_level', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex(['program', 'year_level', 'semester']);
            $table->dropColumn(['program', 'year_level', 'semester', 'units']);
        });
    }
};
