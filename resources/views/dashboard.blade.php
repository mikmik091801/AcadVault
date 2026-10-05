@php
    $user = auth()->user();
    $greetingName = $user->first_name ?: \Illuminate\Support\Str::before($user->name, ' ');

    $statColumns = count($stats) === 3 ? 'col-12 col-sm-6 col-xl-4' : 'col-12 col-sm-6 col-xl-3';

    // Shortcuts to each role's everyday tasks.
    $quickActions = match (true) {
        $user->isAdmin() => [
            ['href' => route('users.create'), 'icon' => 'bi-person-plus', 'title' => 'Add a user', 'description' => 'Create an account and assign its role'],
            ['href' => route('audit-logs.index'), 'icon' => 'bi-clipboard-data', 'title' => 'Review audit logs', 'description' => 'Sign-ins, record views, exports, denials'],
            ['href' => route('records.index'), 'icon' => 'bi-file-earmark-text', 'title' => 'Browse records', 'description' => 'Every academic record in the system'],
        ],
        $user->isRegistrar() => [
            ['href' => route('students.create'), 'icon' => 'bi-person-plus', 'title' => 'Add a student', 'description' => 'Register a new student profile'],
            ['href' => route('records.create'), 'icon' => 'bi-file-earmark-plus', 'title' => 'File a grade', 'description' => 'Record a grade for an enrolled student'],
            ['href' => route('drop-requests.index'), 'icon' => 'bi-hourglass-split', 'title' => 'Review drop requests', 'description' => 'Approve or decline pending drops'],
        ],
        $user->isFaculty() => [
            ['href' => route('courses.index'), 'icon' => 'bi-journal-bookmark', 'title' => 'Open a class list', 'description' => 'Enter grades straight from your roster'],
            ['href' => route('records.create'), 'icon' => 'bi-file-earmark-plus', 'title' => 'File a grade', 'description' => 'Pick a class and a student'],
            ['href' => route('records.index'), 'icon' => 'bi-file-earmark-text', 'title' => 'Grades I have filed', 'description' => 'Review or correct past entries'],
        ],
        default => [
            ['href' => route('enrollments.index'), 'icon' => 'bi-plus-circle', 'title' => 'Enroll in a class', 'description' => 'Browse classes for your program and year'],
            ['href' => route('enrollments.index'), 'icon' => 'bi-file-earmark-check', 'title' => 'Get my certificate', 'description' => 'A QR-verifiable certificate of registration'],
            ['href' => route('exports.index'), 'icon' => 'bi-file-earmark-pdf', 'title' => 'My exported records', 'description' => 'Download documents issued to you'],
        ],
    };
@endphp

<x-app-layout title="Dashboard">

    <x-page-header
        title="Welcome back, {{ $greetingName }}"
        subtitle="{{ $user->role?->label() }} overview · {{ \App\Enums\Program::COLLEGE }}"
        icon="bi-speedometer2" />

    {{-- Summary stat cards --}}
    <div class="row g-3 mb-4 av-stagger">
        @foreach ($stats as $stat)
            <div class="{{ $statColumns }}">
                <x-stat-card
                    :label="$stat['label']"
                    :value="$stat['value']"
                    :icon="$stat['icon']"
                    :variant="$stat['variant']"
                    :hint="$stat['hint'] ?? null"
                    :href="$stat['href'] ?? null" />
            </div>
        @endforeach
    </div>

    {{-- Quick actions --}}
    <h2 class="av-section-title">Quick actions</h2>
    <div class="row g-3 mb-4 av-stagger">
        @foreach ($quickActions as $action)
            <div class="col-md-4">
                <x-quick-action :href="$action['href']" :icon="$action['icon']"
                    :title="$action['title']" :description="$action['description']" />
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
                        <a href="{{ route('audit-logs.index') }}" class="btn btn-sm btn-outline-secondary">
                            View all
                        </a>
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
                                        <th scope="col" class="d-none d-lg-table-cell">Target</th>
                                        <th scope="col" class="d-none d-md-table-cell">IP address</th>
                                        <th scope="col">When</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recentLogs as $log)
                                        <tr>
                                            <td><x-action-badge :action="$log->action" /></td>
                                            <td>
                                                @if ($log->user)
                                                    <div class="fw-semibold">{{ $log->user->name }}</div>
                                                    <div class="small text-body-secondary">{{ $log->user->email }}</div>
                                                @else
                                                    <span class="text-body-secondary fst-italic">Unauthenticated</span>
                                                @endif
                                            </td>
                                            <td class="text-body-secondary d-none d-lg-table-cell">{{ $log->targetLabel() ?? '—' }}</td>
                                            <td class="d-none d-md-table-cell"><code class="av-hash">{{ $log->ip_address ?? '—' }}</code></td>
                                            <td class="text-body-secondary text-nowrap"
                                                title="{{ $log->created_at?->format('d M Y, H:i:s') }}">
                                                {{ $log->created_at?->diffForHumans() }}
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
                            :message="$user->isStudent()
                                ? 'Your grades will appear here once your instructors file them.'
                                : 'Academic records will be listed here once they are added.'" />
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
                                        <th scope="col" class="d-none d-sm-table-cell">Remarks</th>
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
                                        <th scope="col" class="d-none d-md-table-cell">Title</th>
                                        <th scope="col" class="d-none d-sm-table-cell">Students</th>
                                        <th scope="col">Grading</th>
                                        <th scope="col" class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($myCourses as $course)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $course->code }}</div>
                                                {{-- On phones the title rides under the code instead of taking a column. --}}
                                                <div class="small text-body-secondary d-md-none">{{ $course->title }}</div>
                                            </td>
                                            <td class="d-none d-md-table-cell">{{ $course->title }}</td>
                                            <td class="d-none d-sm-table-cell">
                                                <span class="badge badge-status badge-pending">
                                                    <i class="bi bi-people" aria-hidden="true"></i>
                                                    {{ $course->active_enrollments_count }}
                                                </span>
                                            </td>
                                            <td>
                                                @if ($course->ungraded_count > 0)
                                                    <span class="badge badge-status badge-warning-soft">
                                                        <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                                        {{ $course->ungraded_count }} to grade
                                                    </span>
                                                @elseif ($course->active_enrollments_count > 0)
                                                    <span class="badge badge-status badge-authentic">
                                                        <i class="bi bi-check-circle" aria-hidden="true"></i>
                                                        All graded
                                                    </span>
                                                @else
                                                    <span class="text-body-secondary">—</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('courses.show', $course) }}"
                                                   class="btn btn-sm btn-outline-secondary text-nowrap"
                                                   aria-label="Open {{ $course->code }}">
                                                    <span class="d-none d-sm-inline">Open class</span>
                                                    <i class="bi bi-arrow-right ms-sm-1" aria-hidden="true"></i>
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
