<?php

namespace App\Support;

use App\Models\AcademicRecord;

/**
 * Produces the SHA-256 fingerprint of an academic record's content.
 *
 * The hash is taken over the *record*, not over the PDF bytes. That is what
 * makes verification meaningful: the PDF is regenerated on demand, so hashing
 * the file would only prove the file was rendered consistently. Hashing the
 * underlying content means that if anyone edits the grade in the database
 * after the document was issued, the recomputed hash stops matching the one
 * stored at export time, and /verify reports the document as tampered.
 *
 * The canonical form must be byte-identical at export time and at verify
 * time, so field order is fixed and every value is normalised to a string.
 */
class RecordFingerprint
{
    /**
     * Canonical, order-stable representation of the record's content.
     *
     * @return array<string, string>
     */
    public static function canonical(AcademicRecord $record): array
    {
        $record->loadMissing(['student.user', 'course']);

        return [
            'record_id' => (string) $record->id,
            'student_number' => (string) ($record->student?->student_number ?? ''),
            'student_name' => (string) ($record->student?->user?->name ?? ''),
            'program' => (string) ($record->student?->program ?? ''),
            'year_level' => (string) ($record->student?->year_level ?? ''),
            'course_code' => (string) ($record->course?->code ?? ''),
            'course_title' => (string) ($record->course?->title ?? ''),
            'grade' => (string) ($record->grade ?? ''),
            'remarks' => (string) ($record->remarks ?? ''),
        ];
    }

    /**
     * The canonical form serialised exactly as it is hashed.
     */
    public static function payload(AcademicRecord $record): string
    {
        return json_encode(
            self::canonical($record),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );
    }

    /**
     * SHA-256 of the canonical payload, hex encoded (64 characters).
     */
    public static function hash(AcademicRecord $record): string
    {
        return hash('sha256', self::payload($record));
    }
}
