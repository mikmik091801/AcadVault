<x-app-layout title="Drop requests">

    <x-page-header
        title="Drop requests"
        subtitle="Students stay enrolled until you approve their request."
        icon="bi-hourglass-split">
        <x-slot:actions>
            @if ($showAll)
                <a href="{{ route('drop-requests.index') }}" class="btn btn-primary">
                    <i class="bi bi-funnel me-1" aria-hidden="true"></i>Pending only
                </a>
            @else
                <a href="{{ route('drop-requests.index', ['all' => 1]) }}" class="btn btn-light">
                    <i class="bi bi-clock-history me-1" aria-hidden="true"></i>Show reviewed
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="card border-0">
        <div class="card-header bg-white">
            <span class="text-body-secondary small">
                {{ $pendingCount }} {{ Str::plural('request', $pendingCount) }} awaiting review
                @if ($showAll)
                    · showing all requests
                @endif
            </span>
        </div>

        @if ($requests->isEmpty())
            <x-empty-state icon="bi-check2-all"
                title="{{ $showAll ? 'No drop requests yet' : 'Nothing to review' }}"
                message="{{ $showAll ? 'Drop requests will appear here once students submit them.' : 'Every drop request has been dealt with.' }}" />
        @else
            <div class="table-responsive">
                <table class="table av-table table-striped align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Student</th>
                            <th scope="col">Class</th>
                            <th scope="col">Reason</th>
                            <th scope="col">Requested</th>
                            <th scope="col" class="text-end">Decision</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($requests as $enrollment)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $enrollment->student?->user?->name }}</div>
                                    <div class="small text-body-secondary">
                                        {{ $enrollment->student?->student_number }}
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $enrollment->course?->code }}</span>
                                    <div class="small text-body-secondary">{{ $enrollment->course?->title }}</div>
                                </td>
                                <td style="max-width:22rem;">{{ $enrollment->drop_reason }}</td>
                                <td class="text-body-secondary text-nowrap">
                                    {{ $enrollment->drop_requested_at?->format('d M Y') }}
                                    <div class="small">{{ $enrollment->drop_requested_at?->diffForHumans() }}</div>
                                </td>
                                <td class="text-end">
                                    @if ($enrollment->isDropPending())
                                        <button type="button" class="btn btn-sm btn-primary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#reviewModal{{ $enrollment->id }}">
                                            <i class="bi bi-clipboard-check me-1" aria-hidden="true"></i>Review
                                        </button>
                                    @else
                                        <span class="badge badge-status {{ $enrollment->status->badgeClass() }}">
                                            <i class="bi {{ $enrollment->status->icon() }}" aria-hidden="true"></i>
                                            {{ $enrollment->isDropped() ? 'Approved' : 'Declined' }}
                                        </span>
                                        <div class="small text-body-secondary mt-1">
                                            {{ $enrollment->reviewer?->name }}
                                            · {{ $enrollment->reviewed_at?->format('d M Y') }}
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($requests->hasPages())
                <div class="card-body border-top">
                    {{ $requests->links() }}
                </div>
            @endif
        @endif
    </div>

    {{-- ============ Review modals ============ --}}
    @foreach ($requests as $enrollment)
        @can('review', $enrollment)
            <div class="modal fade" id="reviewModal{{ $enrollment->id }}" tabindex="-1"
                 aria-labelledby="reviewModalLabel{{ $enrollment->id }}" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0">
                        <form method="POST" action="{{ route('drop-requests.update', $enrollment) }}">
                            @csrf
                            @method('patch')

                            <div class="modal-header">
                                <h2 class="modal-title h5" id="reviewModalLabel{{ $enrollment->id }}">
                                    Review drop request
                                </h2>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <dl class="av-kv">
                                    <dt>Student</dt>
                                    <dd>
                                        {{ $enrollment->student?->user?->name }}
                                        ({{ $enrollment->student?->student_number }})
                                    </dd>

                                    <dt>Class</dt>
                                    <dd>
                                        {{ $enrollment->course?->code }} —
                                        {{ $enrollment->course?->title }}
                                    </dd>

                                    <dt>Reason given</dt>
                                    <dd>{{ $enrollment->drop_reason }}</dd>
                                </dl>

                                <label for="review_note{{ $enrollment->id }}" class="form-label">
                                    Note to the student <span class="text-body-secondary">(optional)</span>
                                </label>
                                <textarea id="review_note{{ $enrollment->id }}" name="review_note"
                                          rows="3" maxlength="1000" class="form-control"
                                          placeholder="Shown to the student, useful when declining…"></textarea>

                                <div class="alert alert-light border mt-3 mb-0 small">
                                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                                    Approving removes the class from the student's portal and from
                                    any certificate they issue from now on. Declining leaves them
                                    enrolled.
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="decision" value="decline"
                                        class="btn btn-outline-danger">
                                    <i class="bi bi-x-lg me-1" aria-hidden="true"></i>Decline
                                </button>
                                <button type="submit" name="decision" value="approve" class="btn btn-primary">
                                    <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Approve drop
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endcan
    @endforeach

</x-app-layout>
