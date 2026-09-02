@php
    $user = auth()->user();
@endphp

<x-app-layout title="Dashboard">

    <x-page-header
        title="Welcome back, {{ explode(' ', $user->name)[0] }}"
        subtitle="{{ $user->role?->label() }} overview of your AcadVault workspace."
        icon="bi-speedometer2" />

    {{-- Summary stat cards --}}
    <div class="row g-3 mb-4">
        @foreach ($stats as $stat)
            <div class="col-6 col-xl-3">
                <x-stat-card
                    :label="$stat['label']"
                    :value="$stat['value']"
                    :icon="$stat['icon']"
                    :variant="$stat['variant']" />
            </div>
        @endforeach
    </div>

    <div class="row g-4">

        {{-- ADMIN: recent audit activity --}}
        @isset($recentLogs)
            <div class="col-12">
                <div class="card border-0">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <span>
                            <i class="bi bi-clipboard-data text-accent me-2" aria-hidden="true"></i>
                            Recent activity
                        </span>
                    </div>

                    @if ($recentLogs->isEmpty())
                        <x-empty-state icon="bi-clipboard" title="No activity yet"
                            message="Audit entries will appear here as people use the system." />
                    @else
                        <div class="table-responsive">
                            <table class="table av-table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th scope="col">Action</th>
                                        <th scope="col">User</th>
                                        <th scope="col">Target</th>
                                        <th scope="col">IP address</th>
                                        <th scope="col">When</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recentLogs as $log)
                                        <tr>
                                            <td><code class="av-hash">{{ $log->action }}</code></td>
                                            <td>{{ $log->user?->name ?? 'Unknown' }}</td>
                                            <td class="text-body-secondary">{{ $log->targetLabel() ?? '—' }}</td>
                                            <td class="text-body-secondary">{{ $log->ip_address ?? '—' }}</td>
                                            <td class="text-body-secondary">{{ $log->created_at?->diffForHumans() }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        @endisset

        {{-- REGISTRAR / STUDENT: recent records --}}
        @isset($recentRecords)
            <div class="col-12">
                <div class="card border-0">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <span>
                            <i class="bi bi-file-earmark-text text-accent me-2" aria-hidden="true"></i>
                            {{ $user->isStudent() ? 'My latest records' : 'Recently added records' }}
                        </span>
                        <a href="{{ route('records.index') }}" class="btn btn-sm btn-outline-secondary">
                            View all
                        </a>
                    </div>

                    @if ($recentRecords->isEmpty())
                        <x-empty-state icon="bi-file-earmark" title="No records yet"
                            message="Academic records will be listed here once they are added." />
                    @else
                        <div class="table-responsive">
                            <table class="table av-table table-hover align-middle">
                                <thead>
                                    <tr>
                                        @unless ($user->isStudent())
                                            <th scope="col">Student</th>
                                        @endunless
                                        <th scope="col">Course</th>
                                        <th scope="col">Grade</th>
                                        <th scope="col">Remarks</th>
                                        <th scope="col" class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recentRecords as $record)
                                        <tr>
                                            @unless ($user->isStudent())
                                                <td class="fw-semibold">{{ $record->student?->user?->name ?? '—' }}</td>
                                            @endunless
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
        @endisset

        {{-- FACULTY: my courses --}}
        @isset($myCourses)
            <div class="col-12">
                <div class="card border-0">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <span>
                            <i class="bi bi-journal-bookmark text-accent me-2" aria-hidden="true"></i>
                            My courses
                        </span>
                        <a href="{{ route('courses.index') }}" class="btn btn-sm btn-outline-secondary">
                            View all
                        </a>
                    </div>

                    @if ($myCourses->isEmpty())
                        <x-empty-state icon="bi-journal" title="No courses assigned"
                            message="A registrar has not assigned any courses to you yet." />
                    @else
                        <div class="table-responsive">
                            <table class="table av-table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th scope="col">Code</th>
                                        <th scope="col">Title</th>
                                        <th scope="col">Students</th>
                                        <th scope="col">Grades</th>
                                        <th scope="col" class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($myCourses as $course)
                                        <tr>
                                            <td class="fw-semibold">{{ $course->code }}</td>
                                            <td>{{ $course->title }}</td>
                                            <td>
                                                <span class="badge badge-status badge-authentic">
                                                    <i class="bi bi-people" aria-hidden="true"></i>
                                                    {{ $course->active_enrollments_count }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-status badge-pending">
                                                    {{ $course->academic_records_count }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('courses.show', $course) }}"
                                                   class="btn btn-sm btn-outline-secondary">
                                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                                    <span class="visually-hidden">View course</span>
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
        @endisset

        {{-- STUDENT without a linked profile --}}
        @if ($user->isStudent() && ! ($student ?? null))
            <div class="col-12">
                <div class="alert alert-warning d-flex align-items-start gap-2 mb-0" role="alert">
                    <i class="bi bi-exclamation-triangle-fill mt-1" aria-hidden="true"></i>
                    <div>
                        <strong>No student profile linked.</strong>
                        Ask the registrar to link your account to a student number before
                        your academic records can appear here.
                    </div>
                </div>
            </div>
        @endif

    </div>

</x-app-layout>
