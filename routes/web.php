<?php

use App\Http\Controllers\AcademicRecordController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DropRequestController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\EnrollmentDocumentController;
use App\Http\Controllers\EnrollmentVerificationController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TrashedController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    /*
     * Students — admin and registrar only (enforced in the controller's
     * middleware() plus StudentPolicy).
     */
    Route::resource('students', StudentController::class);

    /*
     * Courses — admin/registrar manage; faculty may browse and open only the
     * courses they teach (scoped in the controller and CoursePolicy).
     */
    Route::resource('courses', CourseController::class);

    /*
     * Academic records — every role has an index, scoped per role:
     * admin/registrar see all, faculty see their own courses, students see
     * only their own records.
     */
    Route::resource('records', AcademicRecordController::class)
        ->parameters(['records' => 'record']);

    /*
     * Enrollment — the student's own portal. Enrolling is immediate and needs
     * no approval; dropping requires a reason and a registrar's decision.
     */
    Route::get('enrollments', [EnrollmentController::class, 'index'])->name('enrollments.index');
    Route::post('enrollments', [EnrollmentController::class, 'store'])->name('enrollments.store');
    Route::patch('enrollments/{enrollment}/drop', [EnrollmentController::class, 'requestDrop'])
        ->name('enrollments.drop');

    /*
     * The registrar's review queue for those drop requests.
     */
    Route::get('drop-requests', [DropRequestController::class, 'index'])->name('drop-requests.index');
    Route::patch('drop-requests/{enrollment}', [DropRequestController::class, 'update'])
        ->name('drop-requests.update');

    /*
     * Certificates of registration — the enrolled-class list as a
     * QR-verifiable document.
     */
    Route::post('enrollment-documents', [EnrollmentDocumentController::class, 'store'])
        ->name('enrollment-documents.store');
    Route::get('enrollment-documents/{document}', [EnrollmentDocumentController::class, 'show'])
        ->name('enrollment-documents.show');
    Route::get('enrollment-documents/{document}/download', [EnrollmentDocumentController::class, 'download'])
        ->name('enrollment-documents.download');

    /*
     * Exports — admin/registrar issue documents (ExportPolicy + the record's
     * `export` ability); students may view and download exports of their own
     * records only.
     */
    Route::get('exports', [ExportController::class, 'index'])->name('exports.index');
    Route::get('exports/{export}', [ExportController::class, 'show'])->name('exports.show');
    Route::get('exports/{export}/download', [ExportController::class, 'download'])->name('exports.download');
    Route::post('records/{record}/export', [ExportController::class, 'store'])->name('records.export');

    /*
     * User accounts — admin only. Sign-ups always default to the student
     * role, so this is the only screen that can promote anyone.
     */
    Route::resource('users', UserController::class);

    /*
     * Audit log viewer — admin only. Read-only by design: entries are never
     * editable or deletable from the application.
     */
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

    /*
     * Soft-deleted accounts, students and courses — restorable by staff.
     */
    Route::get('trashed', [TrashedController::class, 'index'])->name('trashed.index');
    Route::post('trashed/students/{id}/restore', [TrashedController::class, 'restoreStudent'])->name('trashed.students.restore');
    Route::post('trashed/courses/{id}/restore', [TrashedController::class, 'restoreCourse'])->name('trashed.courses.restore');
    Route::post('trashed/users/{id}/restore', [TrashedController::class, 'restoreUser'])->name('trashed.users.restore');
});

/*
 * Public document verification. Deliberately outside the auth group: anyone
 * holding a printed transcript must be able to scan its QR and check it.
 */
Route::get('verify/{export}', VerificationController::class)->name('verify');

/*
 * Public verification for a certificate of registration. Kept on its own path
 * so the two document types never collide on a UUID lookup.
 */
Route::get('verify/enrollment/{uuid}', EnrollmentVerificationController::class)
    ->name('verify.enrollment');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
