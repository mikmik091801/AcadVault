<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Encrypts grade/remarks rows that were written before the AcademicRecord
 * model gained its `encrypted` casts. Without this, Eloquent would try to
 * decrypt plaintext and throw a DecryptException on every read.
 *
 * Both directions are idempotent — already-converted rows are skipped — so
 * the migration is safe to re-run.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->convert(function (?string $value): ?string {
            if ($value === null || $this->isEncrypted($value)) {
                return null; // nothing to do
            }

            // Matches the `encrypted` cast exactly: encrypt(value, serialize: false)
            return Crypt::encryptString($value);
        });
    }

    public function down(): void
    {
        $this->convert(function (?string $value): ?string {
            if ($value === null || ! $this->isEncrypted($value)) {
                return null;
            }

            return Crypt::decryptString($value);
        });
    }

    /**
     * Walk every record and rewrite grade/remarks using the given transformer.
     * A null return means "leave this column alone".
     */
    private function convert(callable $transform): void
    {
        DB::table('academic_records')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($transform) {
                foreach ($rows as $row) {
                    $changes = [];

                    if (($grade = $transform($row->grade)) !== null) {
                        $changes['grade'] = $grade;
                    }

                    if (($remarks = $transform($row->remarks)) !== null) {
                        $changes['remarks'] = $remarks;
                    }

                    if ($changes !== []) {
                        DB::table('academic_records')->where('id', $row->id)->update($changes);
                    }
                }
            });
    }

    private function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }
};
