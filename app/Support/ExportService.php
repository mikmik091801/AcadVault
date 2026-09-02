<?php

namespace App\Support;

use App\Models\AcademicRecord;
use App\Models\Export;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfWrapper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ExportService
{
    public function __construct(private QrCodeGenerator $qr) {}

    /**
     * Issue a verifiable export for a record: fingerprint the content, mint a
     * QR pointing at the public verification URL, and persist both.
     */
    public function issue(AcademicRecord $record, User $issuer): Export
    {
        return DB::transaction(function () use ($record, $issuer) {
            $export = Export::create([
                'academic_record_id' => $record->id,
                'exported_by' => $issuer->id,
                'file_hash' => RecordFingerprint::hash($record),
            ]);

            // The QR can only be built once the UUID exists.
            $path = 'qrcodes/'.$export->uuid.'.png';
            Storage::disk('public')->put($path, $this->qr->png($this->verifyUrl($export)));

            $export->forceFill(['qr_code_path' => $path])->save();

            // Producing the document decrypts the grade and puts it in a file
            // that leaves the system — both are audit-worthy.
            AuditLogger::log(AuditLogger::RECORD_DECRYPTED, $record, $issuer);
            AuditLogger::log(AuditLogger::RECORD_EXPORTED, $export, $issuer);

            return $export;
        });
    }

    /**
     * Render the certificate PDF for an export.
     */
    public function pdf(Export $export): PdfWrapper
    {
        $export->loadMissing(['academicRecord.student.user', 'academicRecord.course.faculty', 'exporter']);

        $record = $export->academicRecord;

        return Pdf::loadView('pdf.record', [
            'export' => $export,
            'record' => $record,
            'student' => $record?->student,
            'course' => $record?->course,
            'verifyUrl' => $this->verifyUrl($export),
            // Embedded as a data URI: dompdf renders PNG reliably, and this
            // avoids any dependency on filesystem paths at render time.
            'qrDataUri' => $this->qr->dataUri($this->verifyUrl($export), 260),
            'logo' => PdfAssets::logoDataUri(),
        ])
            ->setPaper('a4')
            ->setOption('isRemoteEnabled', true)
            ->setOption('tempDir', PdfAssets::tempDir());
    }

    /**
     * Filename used when the browser downloads the document.
     */
    public function filename(Export $export): string
    {
        $record = $export->academicRecord;
        $number = $record?->student?->student_number ?? 'record';
        $code = $record?->course?->code ?? '';

        return trim("acadvault-{$number}-{$code}").'.pdf';
    }

    public function verifyUrl(Export $export): string
    {
        return route('verify', $export->uuid);
    }
}
