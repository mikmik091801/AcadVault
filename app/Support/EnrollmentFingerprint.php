<?php

namespace App\Support;

use App\Models\Student;

/**
 * SHA-256 fingerprint of a student's enrollment list.
 *
 * Same philosophy as RecordFingerprint: the hash is taken over the *content*,
 * never over the PDF bytes, because the PDF is regenerated on demand. If the
 * student's enrollment changes after a certificate is issued — a class added,
 * or a drop approved by the registrar — the recomputed hash stops matching and
 * verification reports that the certificate no longer reflects the enrollment.
 *
 * Courses are sorted by code so the payload does not depend on row order.
 */
class EnrollmentFingerprint
{
    /**
     * Canonical, order-stable representation of the student's enrollment.
     *
     * @return array<string, mixed>
     */
    public static function canonical(Student $student): array
    {
        $classes = $student->activeEnrollments()
            ->with('course')
            ->get()
            ->map(fn ($enrollment) => [
                'code' => (string) ($enrollment->course?->code ?? ''),
                'title' => (string) ($enrollment->course?->title ?? ''),
                'status' => $enrollment->status->value,
            ])
            ->sortBy('code')
            ->values()
            ->all();

        return [
            'student_id' => (string) $student->id,
            'student_number' => (string) $student->student_number,
            'student_name' => (string) ($student->user?->name ?? ''),
            'program' => (string) $student->program,
            'year_level' => (string) $student->year_level,
            'classes' => $classes,
        ];
    }

    /**
     * The canonical form serialised exactly as it is hashed.
     */
    public static function payload(Student $student): string
    {
        return json_encode(
            self::canonical($student),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );
    }

    /**
     * SHA-256 of the canonical payload, hex encoded (64 characters).
     */
    public static function hash(Student $student): string
    {
        return hash('sha256', self::payload($student));
    }
}
