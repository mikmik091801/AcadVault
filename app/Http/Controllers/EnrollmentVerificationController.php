<?php

namespace App\Http\Controllers;

use App\Models\EnrollmentDocument;
use App\Support\AuditLogger;
use App\Support\EnrollmentFingerprint;
use Illuminate\View\View;

/**
 * Public verification for a certificate of registration — unauthenticated for
 * the same reason as grade exports: whoever holds the printout must be able to
 * scan it.
 *
 * Note the wording difference from a grade export. A certificate lists the
 * enrollment *as it stood when issued*, and enrollment legitimately changes:
 * adding a class or having a drop approved will make an older certificate stop
 * matching. That is "out of date", not "forged", and the view says so.
 */
class EnrollmentVerificationController extends Controller
{
    public function __invoke(string $uuid): View
    {
        $document = EnrollmentDocument::query()
            ->with(['student.user', 'issuer'])
            ->where('uuid', $uuid)
            ->first();

        if (! $document || ! $document->student) {
            AuditLogger::log(AuditLogger::CERTIFICATE_VERIFIED, 'unknown certificate: '.$uuid);

            return view('verify.enrollment', [
                'status' => 'invalid',
                'document' => null,
                'classes' => collect(),
                'expectedHash' => null,
                'actualHash' => null,
            ]);
        }

        $student = $document->student;
        $actualHash = EnrollmentFingerprint::hash($student);
        $matches = hash_equals($document->file_hash, $actualHash);

        AuditLogger::log(AuditLogger::CERTIFICATE_VERIFIED, $document);

        return view('verify.enrollment', [
            'status' => $matches ? 'authentic' : 'outdated',
            'document' => $document,
            'classes' => $student->activeEnrollments()
                ->with('course')
                ->get()
                ->sortBy(fn ($enrollment) => $enrollment->course?->code)
                ->values(),
            'expectedHash' => $document->file_hash,
            'actualHash' => $actualHash,
        ]);
    }
}
