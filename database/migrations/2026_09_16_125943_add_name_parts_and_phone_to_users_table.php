<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Splits a person's name into the parts a registrar actually files under, and
 * records a contact number.
 *
 * `name` is kept and stays the single display value used across the app, the
 * PDFs and the document fingerprints — the User model recomposes it from these
 * parts on save, so nothing downstream has to change.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('last_name')->nullable()->after('name');
            $table->string('first_name')->nullable()->after('last_name');
            $table->string('middle_initial', 1)->nullable()->after('first_name');
            $table->string('phone', 32)->nullable()->after('middle_initial');
        });

        // Best-effort backfill so existing accounts are not left blank: the
        // last word becomes the surname, everything before it the given name.
        foreach (DB::table('users')->select('id', 'name')->get() as $user) {
            $parts = preg_split('/\s+/', trim((string) $user->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

            if ($parts === []) {
                continue;
            }

            $last = count($parts) > 1 ? array_pop($parts) : '';

            DB::table('users')->where('id', $user->id)->update([
                'first_name' => implode(' ', $parts),
                'last_name' => $last,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_name', 'first_name', 'middle_initial', 'phone']);
        });
    }
};
