<?php

namespace App\Http\Controllers;

use App\Models\EnrollmentDocument;
use App\Support\EnrollmentCertificateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Certificates of registration — the student's enrolled classes as a
 * QR-verifiable PDF.
 */
class EnrollmentDocumentController extends Controller
{
    public function __construct(private EnrollmentCertificateService $certificates) {}

    /**
     * Issue a certificate covering the student's current enrollment.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', EnrollmentDocument::class);

        $student = $request->user()->student;

        abort_unless($student, 403, 'This account has no student profile.');

        // An empty certificate would certify nothing and still carry a QR
        // code, which is worse than refusing to issue it.
        if ($student->activeEnrollments()->doesntExist()) {
            throw ValidationException::withMessages([
                'certificate' => 'Enroll in at least one class before requesting a certificate.',
            ]);
        }

        $document = $this->certificates->issue($student, $request->user());

        return redirect()
            ->route('enrollment-documents.show', $document)
            ->with('success', 'Your certificate of registration is ready.');
    }

    public function show(EnrollmentDocument $document): View
    {
        $this->authorize('view', $document);

        $document->load(['student.user', 'issuer']);

        return view('enrollment-documents.show', [
            'document' => $document,
            'classes' => $document->student
                ->activeEnrollments()
                ->with('course.faculty')
                ->get()
                ->sortBy(fn ($enrollment) => $enrollment->course?->code)
                ->values(),
            'verifyUrl' => $this->certificates->verifyUrl($document),
        ]);
    }

    public function download(EnrollmentDocument $document): Response
    {
        $this->authorize('download', $document);

        return $this->certificates->pdf($document)
            ->download($this->certificates->filename($document));
    }
}
