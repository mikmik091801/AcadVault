<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Certificates of registration.
 *
 * Deliberately a separate table from `exports`: an export certifies exactly
 * one academic record (`academic_record_id` is required and cascades), while
 * a certificate covers a student's whole enrollment list. Widening `exports`
 * to mean both would have made that column nullable and put the existing,
 * tested grade-export flow at risk for no gain.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollment_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();

            $table->foreignId('student_id')->constrained()->cascadeOnDelete();

            $table->foreignId('issued_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('file_hash', 64)->index(); // SHA-256, hex encoded
            $table->string('qr_code_path')->nullable();

            // How many classes the certificate covered when it was issued, so
            // the verification page can say what changed without re-deriving
            // history from the enrollment rows.
            $table->unsignedSmallInteger('class_count')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_documents');
    }
};
