<?php

namespace App\Http\Controllers;

use App\Models\Export;
use App\Support\AuditLogger;
use App\Support\RecordFingerprint;
use Illuminate\View\View;

/**
 * Public document verification — deliberately unauthenticated, because anyone
 * holding a printed transcript must be able to scan its QR and check it.
 */
class VerificationController extends Controller
{
    public function __invoke(string $uuid): View
    {
        $export = Export::query()
            ->with(['academicRecord.student.user', 'academicRecord.course', 'exporter'])
            ->where('uuid', $uuid)
            ->first();

        // An unknown UUID and a record that has since been deleted both fail
        // closed as "invalid" rather than a 404, so a scanned code always
        // lands on a clear answer.
        if (! $export || ! $export->academicRecord) {
            AuditLogger::log(AuditLogger::EXPORT_VERIFIED, 'unknown export: '.$uuid);

            return view('verify.show', [
                'status' => 'invalid',
                'export' => null,
                'record' => null,
                'expectedHash' => null,
                'actualHash' => null,
            ]);
        }

        $record = $export->academicRecord;
        $actualHash = RecordFingerprint::hash($record);
        $authentic = hash_equals($export->file_hash, $actualHash);

        AuditLogger::log(AuditLogger::EXPORT_VERIFIED, $export);

        return view('verify.show', [
            'status' => $authentic ? 'authentic' : 'tampered',
            'export' => $export,
            'record' => $record,
            'expectedHash' => $export->file_hash,
            'actualHash' => $actualHash,
        ]);
    }
}
