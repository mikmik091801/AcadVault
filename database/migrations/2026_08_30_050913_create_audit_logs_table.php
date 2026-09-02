<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Nullable: failed logins have no authenticated user, and entries
            // are deliberately kept after a user account is deleted.
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('action', 60)->index();

            // Polymorphic-style pointer to whatever the action touched.
            $table->string('target_type')->nullable();
            $table->unsignedBigInteger('target_id')->nullable();

            $table->string('ip_address', 45)->nullable(); // 45 = max IPv6 length

            $table->timestamps();

            $table->index(['target_type', 'target_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
