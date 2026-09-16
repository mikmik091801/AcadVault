<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Http\Requests\DropRequestRequest;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * The student's own enrollment portal.
 *
 * Enrolling is immediate and self-service. Dropping is not: the student states
 * a reason and a registrar decides — see DropRequestController.
 */
class EnrollmentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('role:student'),
        ];
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Enrollment::class);

        $student = $this->student($request);

        $enrollments = $student->enrollments()
            ->with(['course.faculty', 'reviewer'])
            ->get()
            ->sortBy(fn (Enrollment $e) => $e->course?->code)
            ->values();

        // Their own curriculum, minus anything they already hold a row for.
        $available = Course::query()
            ->with('faculty')
            ->inCurriculumFor($student)
            ->whereDoesntHave('enrollments', fn ($q) => $q->where('student_id', $student->id))
            ->orderBy('semester')
            ->orderBy('code')
            ->get()
            ->groupBy(fn (Course $course) => $course->semesterLabel() ?? 'Other subjects');

        // Told apart so the empty state can say which it is: a program whose
        // curriculum nobody has entered yet reads very differently to a student
        // who has simply enrolled in everything already.
        $curriculumExists = Course::where('program', $student->program)->exists();

        return view('enrollments.index', [
            'curriculumExists' => $curriculumExists,
            'student' => $student,
            'enrollments' => $enrollments,
            'available' => $available,
            'activeCount' => $enrollments->filter(fn (Enrollment $e) => ! $e->isDropped())->count(),
            'documents' => $student->enrollmentDocuments()->latest()->limit(5)->get(),
        ]);
    }

    /**
     * Enroll in a course. No approval step by design.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Enrollment::class);

        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
        ]);

        $student = $this->student($request);

        // The unique index also guards this, but a friendly error beats a
        // 500 when someone double-submits or re-posts a stale form.
        $existing = $student->enrollments()
            ->where('course_id', $validated['course_id'])
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'course_id' => $existing->isDropped()
                    ? 'You have already dropped this class. Ask the registrar to re-enroll you.'
                    : 'You are already enrolled in this class.',
            ]);
        }

        $enrollment = $student->enrollments()->create([
            'course_id' => $validated['course_id'],
            'status' => EnrollmentStatus::Enrolled,
        ]);

        AuditLogger::log(AuditLogger::ENROLLED, $enrollment);

        return redirect()
            ->route('enrollments.index')
            ->with('success', "You are now enrolled in {$enrollment->course?->code}.");
    }

    /**
     * Ask the registrar to drop a class, with a reason.
     */
    public function requestDrop(DropRequestRequest $request, Enrollment $enrollment): RedirectResponse
    {
        $this->authorize('requestDrop', $enrollment);

        $enrollment->forceFill([
            'status' => EnrollmentStatus::DropPending,
            'drop_reason' => $request->validated()['drop_reason'],
            'drop_requested_at' => now(),
            // A fresh request clears any previous decision.
            'reviewed_by' => null,
            'reviewed_at' => null,
            'review_note' => null,
        ])->save();

        AuditLogger::log(AuditLogger::DROP_REQUESTED, $enrollment);

        return redirect()
            ->route('enrollments.index')
            ->with('success', 'Your drop request was sent to the registrar for review.');
    }

    /**
     * The signed-in student's profile, or a 403 if they have none.
     */
    private function student(Request $request): Student
    {
        $student = $request->user()->student;

        abort_unless($student, 403, 'This account has no student profile.');

        return $student;
    }
}
