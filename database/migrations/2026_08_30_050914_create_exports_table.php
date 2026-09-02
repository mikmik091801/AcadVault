<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exports', function (Blueprint $table) {
            $table->id();

            // Public identifier used by /verify/{export}. Binding the verify
            // route to a UUID rather than the auto-increment id stops anyone
            // walking 1,2,3... through other students' documents.
            $table->uuid('uuid')->unique();

            $table->foreignId('academic_record_id')->constrained()->cascadeOnDelete();

            $table->foreignId('exported_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('file_hash', 64)->index(); // SHA-256, hex encoded
            $table->string('qr_code_path')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exports');
    }
};
