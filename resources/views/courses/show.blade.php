<x-app-layout title="Course details">

    <x-page-header
        title="{{ $course->code }}"
        subtitle="{{ $course->title }}"
        icon="bi-journal-bookmark">
        <x-slot:actions>
            <a href="{{ route('courses.index') }}" class="btn btn-light">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back
            </a>
            @can('update', $course)
                <a href="{{ route('courses.edit', $course) }}" class="btn btn-primary">
                    <i class="bi bi-pencil me-1" aria-hidden="true"></i>Edit
                </a>
            @endcan
            @can('delete', $course)
                <button type="button" class="btn btn-outline-danger"
                        data-bs-toggle="modal" data-bs-target="#deleteCourseModal">
                    <i class="bi bi-trash3 me-1" aria-hidden="true"></i>Delete
                </button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 h-100">
                <div class="card-header bg-white">
                    <i class="bi bi-info-circle text-accent me-2" aria-hidden="true"></i>Details
                </div>
                <div class="card-body">
                    <dl class="av-kv mb-0">
                        <dt>Course code</dt>
                        <dd>{{ $course->code }}</dd>

                        <dt>Title</dt>
                        <dd>{{ $course->title }}</dd>

                        <dt>Instructor</dt>
                        <dd>
                            @if ($course->faculty)
                                {{ $course->faculty->name }}
                                <div class="small text-body-secondary fw-normal">{{ $course->faculty->email }}</div>
                            @else
                                <span class="badge badge-status badge-pending">
                                    <i class="bi bi-dash-circle" aria-hidden="true"></i>Unassigned
                                </span>
                            @endif
                        </dd>

                        <dt>Students enrolled</dt>
                        <dd>
                            <span class="badge badge-status badge-authentic">
                                <i class="bi bi-people" aria-hidden="true"></i>
                                {{ $roster->count() }}
                            </span>
                        </dd>

                        <dt>Created</dt>
                        <dd>{{ $course->created_at?->format('d M Y') }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            {{-- Who is actually taking the class, straight from enrolments. --}}
            <div class="card border-0 mb-4">
                <div class="card-header bg-white">
                    <i class="bi bi-people text-accent me-2" aria-hidden="true"></i>
                    Class list
                    <span class="badge badge-status badge-pending ms-1">{{ $roster->count() }}</span>
                </div>

                @if ($roster->isEmpty())
                    <x-empty-state icon="bi-people" title="Nobody has enrolled yet"
                        message="Students who enrol in this class will appear here." />
                @else
                    <div class="table-responsive">
                        <table class="table av-table table-striped table-hover align-middle">
                            <thead>
                                <tr>
                                    <th scope="col">Student</th>
                                    <th scope="col" class="d-none d-md-table-cell">Program</th>
                                    <th scope="col">Status</th>
                                    <th scope="col" class="text-end">Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($roster as $enrollment)
                                    @php
                                        $grade = $gradesByStudent->get($enrollment->student_id);
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $enrollment->student?->user?->name }}</div>
                                            <code class="av-hash text-nowrap">{{ $enrollment->student?->student_number }}</code>
                                        </td>
                                        <td class="d-none d-md-table-cell">
                                            <span class="badge badge-status badge-role" title="{{ $enrollment->student?->program }}">
                                                {{ \App\Enums\Program::shortNameFor($enrollment->student?->program) }}
                                            </span>
                                            <div class="small text-body-secondary">{{ $enrollment->student?->yearLevelLabel() }}</div>
                                        </td>
                                        <td>
                                            <span class="badge badge-status {{ $enrollment->status->badgeClass() }}">
                                                <i class="bi {{ $enrollment->status->icon() }}" aria-hidden="true"></i>
                                                {{ $enrollment->status->label() }}
                                            </span>
                                        </td>
                                        <td class="text-end text-nowrap">
                                            @if ($grade)
                                                <a href="{{ route('records.show', $grade) }}"
                                                   class="av-grade-chip"
                                                   aria-label="View grade for {{ $enrollment->student?->user?->name }}">
                                                    {{ $grade->grade }}
                                                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                                                </a>
                                            @elseif ($canGrade)
                                                <a href="{{ route('records.create', ['course_id' => $course->id, 'student_id' => $enrollment->student_id, 'return_to_course' => 1]) }}"
                                                   class="btn btn-sm btn-outline-accent"
                                                   aria-label="Enter grade for {{ $enrollment->student?->user?->name }}">
                                                    <i class="bi bi-plus-lg me-sm-1" aria-hidden="true"></i><span class="d-none d-sm-inline">Enter grade</span>
                                                </a>
                                            @else
                                                <span class="text-body-secondary">—</span>
                                            @endif
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
                    {{-- Not the same thing as the class list: these are filed grades. --}}
                    Grades filed
                    <span class="badge badge-status badge-pending ms-1">
                        {{ $course->academicRecords->count() }}
                    </span>
                </div>

                @if ($course->academicRecords->isEmpty())
                    <x-empty-state icon="bi-file-earmark" title="No records yet"
                        message="No grades have been filed for this course." />
                @else
                    <div class="table-responsive">
                        <table class="table av-table table-striped table-hover align-middle">
                            <thead>
                                <tr>
                                    <th scope="col">Student</th>
                                    <th scope="col">Grade</th>
                                    <th scope="col" class="d-none d-sm-table-cell">Remarks</th>
                                    <th scope="col" class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($course->academicRecords as $record)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $record->student?->user?->name }}</div>
                                            <div class="small text-body-secondary">
                                                {{ $record->student?->student_number }}
                                            </div>
                                            {{-- Grades filed before enrolment existed, or for a
                                                 student who has since dropped. --}}
                                            @unless ($roster->contains('student_id', $record->student_id))
                                                <span class="badge badge-status badge-warning-soft mt-1"
                                                      title="This student is not currently enrolled in this course.">
                                                    <i class="bi bi-exclamation-circle" aria-hidden="true"></i>Not on class list
                                                </span>
                                            @endunless
                                        </td>
                                        <td class="fw-semibold">{{ $record->grade }}</td>
                                        <td class="text-body-secondary d-none d-sm-table-cell">{{ $record->remarks ?? '—' }}</td>
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

    @can('delete', $course)
        <div class="modal fade" id="deleteCourseModal" tabindex="-1"
             aria-labelledby="deleteCourseModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0">
                    <form method="POST" action="{{ route('courses.destroy', $course) }}">
                        @csrf
                        @method('delete')

                        <div class="modal-header">
                            <h2 class="modal-title h5" id="deleteCourseModalLabel">Delete this course?</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            <p class="mb-0">
                                Deleting <strong>{{ $course->code }}</strong> also removes its
                                <strong>{{ $course->academicRecords->count() }}</strong>
                                academic {{ Str::plural('record', $course->academicRecords->count()) }}.
                                This cannot be undone.
                            </p>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-trash3 me-1" aria-hidden="true"></i>Delete course
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

</x-app-layout>
