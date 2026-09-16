<x-app-layout title="My Classes">

    <x-page-header
        title="My Classes"
        subtitle="Enroll yourself in a class, or ask the registrar to drop one."
        icon="bi-journal-bookmark">
        <x-slot:actions>
            @if ($activeCount > 0)
                <form method="POST" action="{{ route('enrollment-documents.store') }}">
                    @csrf
                    <button type="submit" class="btn btn-accent">
                        <i class="bi bi-file-earmark-check me-1" aria-hidden="true"></i>
                        Get certificate
                    </button>
                </form>
            @endif
        </x-slot:actions>
    </x-page-header>

    @error('certificate')
        <div class="alert alert-warning d-flex align-items-start gap-2" role="alert">
            <i class="bi bi-exclamation-triangle-fill mt-1" aria-hidden="true"></i>
            <div>{{ $message }}</div>
        </div>
    @enderror

    @error('course_id')
        <div class="alert alert-warning d-flex align-items-start gap-2" role="alert">
            <i class="bi bi-exclamation-triangle-fill mt-1" aria-hidden="true"></i>
            <div>{{ $message }}</div>
        </div>
    @enderror

    {{-- ============ Currently enrolled ============ --}}
    <div class="card border-0 mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span>
                <i class="bi bi-mortarboard text-accent me-2" aria-hidden="true"></i>
                My classes
                <span class="badge badge-status badge-pending ms-1">{{ $activeCount }}</span>
            </span>
        </div>

        @if ($enrollments->isEmpty())
            <x-empty-state icon="bi-journal-bookmark" title="You are not enrolled in anything yet"
                message="Pick a class from the list below to enroll. It takes effect straight away." />
        @else
            <div class="table-responsive">
                <table class="table av-table table-striped align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Code</th>
                            <th scope="col">Course</th>
                            <th scope="col">Instructor</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($enrollments as $enrollment)
                            <tr>
                                <td><code class="av-hash">{{ $enrollment->course?->code }}</code></td>
                                <td>
                                    <div class="fw-semibold">{{ $enrollment->course?->title }}</div>
                                    @if ($enrollment->isDropPending())
                                        <div class="small text-body-secondary">
                                            Requested {{ $enrollment->drop_requested_at?->diffForHumans() }}:
                                            “{{ $enrollment->drop_reason }}”
                                        </div>
                                    @elseif ($enrollment->isDropped())
                                        <div class="small text-body-secondary">
                                            Dropped {{ $enrollment->reviewed_at?->format('d M Y') }}
                                            @if ($enrollment->reviewer)
                                                by {{ $enrollment->reviewer->name }}
                                            @endif
                                        </div>
                                    @elseif ($enrollment->reviewed_at && $enrollment->review_note)
                                        {{-- A declined request leaves the class active. --}}
                                        <div class="small text-danger">
                                            Drop declined: {{ $enrollment->review_note }}
                                        </div>
                                    @endif
                                </td>
                                <td>{{ $enrollment->course?->faculty?->name ?? 'Unassigned' }}</td>
                                <td>
                                    <span class="badge badge-status {{ $enrollment->status->badgeClass() }}">
                                        <i class="bi {{ $enrollment->status->icon() }}" aria-hidden="true"></i>
                                        {{ $enrollment->status->label() }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    @can('requestDrop', $enrollment)
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#dropModal{{ $enrollment->id }}">
                                            <i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i>
                                            Request drop
                                        </button>
                                    @elseif ($enrollment->isDropPending())
                                        <span class="small text-body-secondary">Awaiting registrar</span>
                                    @else
                                        <span class="text-body-secondary">—</span>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ============ Available to enroll ============ ---}}
    <div class="card border-0 mb-4">
        <div class="card-header bg-white">
            <i class="bi bi-plus-circle text-accent me-2" aria-hidden="true"></i>
            Available classes
            <span class="small text-body-secondary ms-2">
                {{ $student->program }} · {{ $student->yearLevelLabel() }}
            </span>
        </div>

        @if ($available->isEmpty() && ! $curriculumExists)
            <x-empty-state icon="bi-journal-x" title="No curriculum for your program yet"
                message="The subjects for {{ $student->program }} have not been entered into AcadVault. Ask the registrar to add them." />
        @elseif ($available->isEmpty())
            <x-empty-state icon="bi-check2-all" title="Nothing left to enroll in"
                message="Every subject in your curriculum for this year is already on your list." />
        @else
            @foreach ($available as $semester => $courses)
                <div class="card-body border-top py-2 bg-light">
                    <span class="small fw-semibold text-uppercase text-body-secondary"
                          style="letter-spacing:.06em;">{{ $semester }}</span>
                </div>

                <div class="table-responsive">
                    <table class="table av-table table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Code</th>
                                <th scope="col">Subject</th>
                                <th scope="col">Units</th>
                                <th scope="col">Instructor</th>
                                <th scope="col" class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($courses as $course)
                                <tr>
                                    <td><code class="av-hash">{{ $course->code }}</code></td>
                                    <td class="fw-semibold">{{ $course->title }}</td>
                                    <td>{{ $course->units }}</td>
                                    <td>{{ $course->faculty?->name ?? 'Unassigned' }}</td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('enrollments.store') }}"
                                              class="d-inline">
                                            @csrf
                                            <input type="hidden" name="course_id" value="{{ $course->id }}">
                                            <button type="submit" class="btn btn-sm btn-primary">
                                                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Enroll
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        @endif
    </div>

    {{-- ============ Certificates already issued ============ --}}
    @if ($documents->isNotEmpty())
        <div class="card border-0">
            <div class="card-header bg-white">
                <i class="bi bi-file-earmark-check text-accent me-2" aria-hidden="true"></i>
                My certificates
            </div>
            <div class="table-responsive">
                <table class="table av-table table-striped align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Document ID</th>
                            <th scope="col">Classes</th>
                            <th scope="col">Issued</th>
                            <th scope="col" class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($documents as $document)
                            <tr>
                                <td><code class="av-hash">{{ $document->uuid }}</code></td>
                                <td>{{ $document->class_count }}</td>
                                <td class="text-body-secondary text-nowrap">
                                    {{ $document->created_at?->format('d M Y, H:i') }}
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('enrollment-documents.show', $document) }}"
                                       class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                        <span class="visually-hidden">Open certificate</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ============ Drop request modals ============ --}}
    @foreach ($enrollments as $enrollment)
        @can('requestDrop', $enrollment)
            <div class="modal fade" id="dropModal{{ $enrollment->id }}" tabindex="-1"
                 aria-labelledby="dropModalLabel{{ $enrollment->id }}" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0">
                        <form method="POST" action="{{ route('enrollments.drop', $enrollment) }}">
                            @csrf
                            @method('patch')

                            <div class="modal-header">
                                <h2 class="modal-title h5" id="dropModalLabel{{ $enrollment->id }}">
                                    Request to drop {{ $enrollment->course?->code }}
                                </h2>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <p class="text-body-secondary">
                                    The registrar reviews every drop request. You stay enrolled in
                                    <strong>{{ $enrollment->course?->title }}</strong> until they approve it.
                                </p>

                                <label for="drop_reason{{ $enrollment->id }}" class="form-label">
                                    Reason for dropping
                                </label>
                                <textarea id="drop_reason{{ $enrollment->id }}" name="drop_reason"
                                          rows="4" required minlength="10" maxlength="1000"
                                          class="form-control @error('drop_reason') is-invalid @enderror"
                                          placeholder="Explain why you need to drop this class…"></textarea>
                                @error('drop_reason')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">At least 10 characters.</div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger">
                                    <i class="bi bi-send me-1" aria-hidden="true"></i>Send request
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endcan
    @endforeach

</x-app-layout>
