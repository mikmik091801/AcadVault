<?php

namespace App\Support;

use App\Models\EnrollmentDocument;
use App\Models\Student;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfWrapper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Issues and renders the certificate of registration — the student's list of
 * enrolled classes as a QR-verifiable document.
 *
 * Mirrors ExportService deliberately: same fingerprint-then-QR sequence, same
 * dompdf hardening. See ExportService::pdf() for why the temp directory is
 * pinned and the logo is flattened.
 */
class EnrollmentCertificateService
{
    public function __construct(private QrCodeGenerator $qr) {}

    /**
     * Issue a certificate covering the student's current enrollment.
     */
    public function issue(Student $student, User $issuer): EnrollmentDocument
    {
        return DB::transaction(function () use ($student, $issuer) {
            $document = EnrollmentDocument::create([
                'student_id' => $student->id,
                'issued_by' => $issuer->id,
                'file_hash' => EnrollmentFingerprint::hash($student),
                'class_count' => $student->activeEnrollments()->count(),
            ]);

            // The QR can only be built once the UUID exists.
            $path = 'qrcodes/'.$document->uuid.'.png';
            Storage::disk('public')->put($path, $this->qr->png($this->verifyUrl($document)));

            $document->forceFill(['qr_code_path' => $path])->save();

            AuditLogger::log(AuditLogger::CERTIFICATE_ISSUED, $document, $issuer);

            return $document;
        });
    }

    /**
     * Render the certificate PDF.
     */
    public function pdf(EnrollmentDocument $document): PdfWrapper
    {
        $document->loadMissing(['student.user', 'issuer']);
        $student = $document->student;

        $classes = $student->activeEnrollments()
            ->with('course.faculty')
            ->get()
            ->sortBy(fn ($enrollment) => $enrollment->course?->code)
            ->values();

        return Pdf::loadView('pdf.enrollment', [
            'document' => $document,
            'student' => $student,
            'classes' => $classes,
            'verifyUrl' => $this->verifyUrl($document),
            'qrDataUri' => $this->qr->dataUri($this->verifyUrl($document), 260),
            'logo' => PdfAssets::logoDataUri(),
        ])
            ->setPaper('a4')
            ->setOption('isRemoteEnabled', true)
            ->setOption('tempDir', PdfAssets::tempDir());
    }

    /**
     * Filename used when the browser downloads the certificate.
     */
    public function filename(EnrollmentDocument $document): string
    {
        $number = $document->student?->student_number ?? 'student';

        return "acadvault-cor-{$number}.pdf";
    }

    public function verifyUrl(EnrollmentDocument $document): string
    {
        return route('verify.enrollment', $document->uuid);
    }
}
