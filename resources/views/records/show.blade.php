<x-app-layout title="Academic record">

    <x-page-header
        title="Academic record"
        subtitle="{{ $record->student?->user?->name }} · {{ $record->course?->code }} {{ $record->course?->title }}"
        icon="bi-file-earmark-text">
        <x-slot:actions>
            <a href="{{ route('records.index') }}" class="btn btn-light">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back
            </a>
            @can('export', $record)
                <form method="POST" action="{{ route('records.export', $record) }}">
                    @csrf
                    <button type="submit" class="btn btn-accent">
                        <i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i>Export PDF
                    </button>
                </form>
            @endcan
            @can('update', $record)
                <a href="{{ route('records.edit', $record) }}" class="btn btn-primary">
                    <i class="bi bi-pencil me-1" aria-hidden="true"></i>Edit
                </a>
            @endcan
            @can('delete', $record)
                <button type="button" class="btn btn-outline-danger"
                        data-bs-toggle="modal" data-bs-target="#deleteRecordModal">
                    <i class="bi bi-trash3 me-1" aria-hidden="true"></i>Delete
                </button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 h-100">
                <div class="card-header bg-white">
                    <i class="bi bi-card-checklist text-accent me-2" aria-hidden="true"></i>Record details
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <dl class="av-kv mb-0">
                                <dt>Student</dt>
                                <dd>
                                    {{ $record->student?->user?->name }}
                                    <div class="small text-body-secondary fw-normal">
                                        {{ $record->student?->student_number }}
                                    </div>
                                </dd>

                                <dt>Program</dt>
                                <dd>{{ $record->student?->program }}</dd>

                                <dt>Course</dt>
                                <dd>
                                    {{ $record->course?->code }}
                                    <div class="small text-body-secondary fw-normal">
                                        {{ $record->course?->title }}
                                    </div>
                                </dd>
                            </dl>
                        </div>

                        <div class="col-sm-6">
                            <dl class="av-kv mb-0">
                                <dt>Grade</dt>
                                <dd class="fs-4 fw-bold" style="color:var(--av-navy-900);">{{ $record->grade }}</dd>

                                <dt>Remarks</dt>
                                <dd>{{ $record->remarks ?? '—' }}</dd>

                                <dt>Instructor</dt>
                                <dd>{{ $record->course?->faculty?->name ?? 'Unassigned' }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 h-100">
                <div class="card-header bg-white">
                    <i class="bi bi-shield-lock text-accent me-2" aria-hidden="true"></i>Provenance
                </div>
                <div class="card-body">
                    <dl class="av-kv mb-3">
                        <dt>Recorded by</dt>
                        <dd>{{ $record->creator?->name ?? 'Unknown' }}</dd>

                        <dt>Created</dt>
                        <dd>{{ $record->created_at?->format('d M Y, H:i') }}</dd>

                        <dt>Last updated</dt>
                        <dd>{{ $record->updated_at?->format('d M Y, H:i') }}</dd>
                    </dl>

                    <div class="alert alert-light border d-flex align-items-start gap-2 mb-0" role="note">
                        <i class="bi bi-info-circle text-accent mt-1" aria-hidden="true"></i>
                        <div class="small text-body-secondary">
                            The grade and remarks on this page are decrypted for display.
                            This view has been written to the audit log.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @can('delete', $record)
        <div class="modal fade" id="deleteRecordModal" tabindex="-1"
             aria-labelledby="deleteRecordModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0">
                    <form method="POST" action="{{ route('records.destroy', $record) }}">
                        @csrf
                        @method('delete')

                        <div class="modal-header">
                            <h2 class="modal-title h5" id="deleteRecordModalLabel">Delete this record?</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            <p class="mb-0">
                                This permanently removes the grade for
                                <strong>{{ $record->student?->user?->name }}</strong> in
                                <strong>{{ $record->course?->code }}</strong>. This cannot be undone.
                            </p>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-trash3 me-1" aria-hidden="true"></i>Delete record
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

</x-app-layout>
