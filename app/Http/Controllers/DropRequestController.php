<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

/**
 * The registrar's queue of drop requests.
 *
 * Approving flips the enrollment to dropped, which takes the class off the
 * student's portal and off their certificate immediately — there is no
 * separate publish step.
 */
class DropRequestController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('role:admin,registrar'),
        ];
    }

    public function index(Request $request): View
    {
        $showAll = $request->boolean('all');

        $requests = Enrollment::query()
            ->with(['student.user', 'course', 'reviewer'])
            ->when(! $showAll, fn ($q) => $q->awaitingReview())
            // Reviewed rows only appear in the "all" view, newest decision first.
            ->when($showAll, fn ($q) => $q->whereNotNull('drop_requested_at'))
            ->orderByDesc('drop_requested_at')
            ->paginate(15)
            ->withQueryString();

        return view('drop-requests.index', [
            'requests' => $requests,
            'showAll' => $showAll,
            'pendingCount' => Enrollment::awaitingReview()->count(),
        ]);
    }

    /**
     * Approve or decline one request.
     */
    public function update(Request $request, Enrollment $enrollment): RedirectResponse
    {
        $this->authorize('review', $enrollment);

        $validated = $request->validate([
            'decision' => ['required', 'in:approve,decline'],
            'review_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $approved = $validated['decision'] === 'approve';

        $enrollment->forceFill([
            // Declining puts the class back exactly as it was.
            'status' => $approved ? EnrollmentStatus::Dropped : EnrollmentStatus::Enrolled,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_note' => $validated['review_note'] ?? null,
        ])->save();

        AuditLogger::log(
            $approved ? AuditLogger::DROP_APPROVED : AuditLogger::DROP_DECLINED,
            $enrollment,
        );

        $enrollment->loadMissing('student.user', 'course');
        $enrollment->student?->user?->notify(new \App\Notifications\DropRequestReviewed($enrollment, $approved));

        $name = $enrollment->student?->user?->name;
        $code = $enrollment->course?->code;

        return redirect()
            ->route('drop-requests.index')
            ->with('success', $approved
                ? "{$name} has been dropped from {$code}."
                : "The drop request from {$name} for {$code} was declined.");
    }
}
