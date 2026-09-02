<x-app-layout title="Student details">

    <x-page-header
        title="{{ $student->user?->name }}"
        subtitle="{{ $student->student_number }} · {{ $student->program }}"
        icon="bi-mortarboard">
        <x-slot:actions>
            <a href="{{ route('students.index') }}" class="btn btn-light">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back
            </a>
            @can('update', $student)
                <a href="{{ route('students.edit', $student) }}" class="btn btn-primary">
                    <i class="bi bi-pencil me-1" aria-hidden="true"></i>Edit
                </a>
            @endcan
            @can('delete', $student)
                <button type="button" class="btn btn-outline-danger"
                        data-bs-toggle="modal" data-bs-target="#deleteStudentModal">
                    <i class="bi bi-trash3 me-1" aria-hidden="true"></i>Delete
                </button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 h-100">
                <div class="card-header bg-white">
                    <i class="bi bi-person-vcard text-accent me-2" aria-hidden="true"></i>Profile
                </div>
                <div class="card-body">
                    <dl class="av-kv mb-0">
                        <dt>Full name</dt>
                        <dd>{{ $student->user?->name }}</dd>

                        <dt>Email</dt>
                        <dd>{{ $student->user?->email }}</dd>

                        <dt>Student number</dt>
                        <dd><code class="av-hash">{{ $student->student_number }}</code></dd>

                        <dt>Program</dt>
                        <dd>{{ $student->program }}</dd>

                        <dt>Year level</dt>
                        <dd>{{ $student->yearLevelLabel() }}</dd>

                        <dt>Role</dt>
                        <dd><x-role-badge :role="$student->user?->role" /></dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            {{-- Classes in progress. For faculty this is narrowed to their own. --}}
            <div class="card border-0 mb-4">
                <div class="card-header bg-white">
                    <i class="bi bi-journal-bookmark text-accent me-2" aria-hidden="true"></i>
                    Enrolled classes
                    <span class="badge badge-status badge-pending ms-1">{{ $classes->count() }}</span>
                </div>

                @if ($classes->isEmpty())
                    <x-empty-state icon="bi-journal" title="No enrolled classes"
                        message="This student is not currently enrolled in anything." />
                @else
                    <div class="table-responsive">
                        <table class="table av-table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th scope="col">Code</th>
                                    <th scope="col">Course</th>
                                    <th scope="col">Instructor</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($classes as $enrollment)
                                    <tr>
                                        <td><code class="av-hash">{{ $enrollment->course?->code }}</code></td>
                                        <td>{{ $enrollment->course?->title }}</td>
                                        <td>{{ $enrollment->course?->faculty?->name ?? 'Unassigned' }}</td>
                                        <td>
                                            <span class="badge badge-status {{ $enrollment->status->badgeClass() }}">
                                                <i class="bi {{ $enrollment->status->icon() }}" aria-hidden="true"></i>
                                                {{ $enrollment->status->label() }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="card border-0">
                <div class="card-header bg-white">
                    <i class="bi bi-file-earmark-text text-accent me-2" aria-hidden="true"></i>
                    Academic records
                    <span class="badge badge-status badge-pending ms-1">
                        {{ $student->academicRecords->count() }}
                    </span>
                </div>

                @if ($student->academicRecords->isEmpty())
                    <x-empty-state icon="bi-file-earmark" title="No records yet"
                        message="This student has no academic records on file." />
                @else
                    <div class="table-responsive">
                        <table class="table av-table table-striped table-hover align-middle">
                            <thead>
                                <tr>
                                    <th scope="col">Course</th>
                                    <th scope="col">Grade</th>
                                    <th scope="col">Remarks</th>
                                    <th scope="col" class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($student->academicRecords as $record)
                                    <tr>
                                        <td>
                                            <span class="fw-semibold">{{ $record->course?->code }}</span>
                                            <div class="small text-body-secondary">{{ $record->course?->title }}</div>
                                        </td>
                                        <td class="fw-semibold">{{ $record->grade }}</td>
                                        <td class="text-body-secondary">{{ $record->remarks ?? '—' }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('records.show', $record) }}"
                                               class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-eye" aria-hidden="true"></i>
                                                <span class="visually-hidden">View record</span>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @can('delete', $student)
        <div class="modal fade" id="deleteStudentModal" tabindex="-1"
             aria-labelledby="deleteStudentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0">
                    <form method="POST" action="{{ route('students.destroy', $student) }}">
                        @csrf
                        @method('delete')

                        <div class="modal-header">
                            <h2 class="modal-title h5" id="deleteStudentModalLabel">Delete this student?</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            <p class="mb-0">
                                Deleting <strong>{{ $student->user?->name }}</strong> also removes their
                                <strong>{{ $student->academicRecords->count() }}</strong>
                                academic {{ Str::plural('record', $student->academicRecords->count()) }}.
                                This cannot be undone.
                            </p>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-trash3 me-1" aria-hidden="true"></i>Delete student
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

</x-app-layout>
