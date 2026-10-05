<x-app-layout title="Courses">

    <x-page-header
        title="{{ auth()->user()->isFaculty() ? 'My courses' : 'Courses' }}"
        subtitle="{{ auth()->user()->isFaculty() ? 'Courses you are assigned to teach.' : 'Course catalogue and assigned instructors.' }}"
        icon="bi-journal-bookmark">
        <x-slot:actions>
            @can('create', App\Models\Course::class)
                <a href="{{ route('courses.create') }}" class="btn btn-accent">
                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add course
                </a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="card border-0">
        <div class="card-header bg-white">
            {{-- One pill per College of Computing Education program. --}}
            <nav class="av-filter-pills mb-3" aria-label="Filter by program">
                <a href="{{ request()->fullUrlWithQuery(['program' => null, 'page' => null]) }}"
                   class="av-filter-pill {{ $program ? '' : 'active' }}"
                   @unless ($program) aria-current="page" @endunless>All programs</a>
                @foreach (\App\Enums\Program::cases() as $case)
                    <a href="{{ request()->fullUrlWithQuery(['program' => $case->shortName(), 'page' => null]) }}"
                       class="av-filter-pill {{ $program === $case ? 'active' : '' }}"
                       title="{{ $case->value }}"
                       @if ($program === $case) aria-current="page" @endif>{{ $case->shortName() }}</a>
                @endforeach
            </nav>

            <div class="row g-2 align-items-center">
                <div class="col-md-6">
                    <span class="text-body-secondary small">
                        {{ $courses->total() }} {{ Str::plural('course', $courses->total()) }} found
                        @if ($program)
                            in <span class="fw-semibold">{{ $program->value }}</span>
                        @endif
                    </span>
                </div>
                <div class="col-md-6">
                    <x-search-box :value="$search" placeholder="Search code, title or instructor…"
                        :keep="['program' => $program?->shortName()]" />
                </div>
            </div>
        </div>

        @if ($courses->isEmpty())
            <x-empty-state icon="bi-journal"
                title="{{ $search !== '' ? 'No courses match your search' : ($program ? 'No '.$program->shortName().' subjects yet' : 'No courses yet') }}"
                message="{{ auth()->user()->isFaculty()
                    ? 'You have not been assigned any courses.'
                    : ($program ? 'Add a course and set its program to '.$program->value.'.' : 'Add a course to get started.') }}" />
        @else
            <div class="table-responsive">
                <table class="table av-table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Code</th>
                            <th scope="col">Title</th>
                            <th scope="col" class="d-none d-lg-table-cell">Program</th>
                            <th scope="col">Instructor</th>
                            <th scope="col">Students</th>
                            <th scope="col">Grades</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($courses as $course)
                            <tr>
                                <td><span class="fw-semibold text-nowrap">{{ $course->code }}</span></td>
                                <td>{{ $course->title }}</td>
                                <td class="d-none d-lg-table-cell">
                                    @if ($course->program)
                                        <span class="badge badge-status badge-role" title="{{ $course->program }}">
                                            {{ \App\Enums\Program::shortNameFor($course->program) }}
                                        </span>
                                    @else
                                        <span class="text-body-secondary small">All programs</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($course->faculty)
                                        {{ $course->faculty->name }}
                                    @else
                                        <span class="badge badge-status badge-pending">
                                            <i class="bi bi-dash-circle" aria-hidden="true"></i>Unassigned
                                        </span>
                                    @endif
                                </td>
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
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('courses.show', $course) }}"
                                           class="btn btn-outline-secondary" title="View">
                                            <i class="bi bi-eye" aria-hidden="true"></i>
                                            <span class="visually-hidden">View {{ $course->code }}</span>
                                        </a>
                                        @can('update', $course)
                                            <a href="{{ route('courses.edit', $course) }}"
                                               class="btn btn-outline-secondary" title="Edit">
                                                <i class="bi bi-pencil" aria-hidden="true"></i>
                                                <span class="visually-hidden">Edit {{ $course->code }}</span>
                                            </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($courses->hasPages())
                <div class="card-body border-top">
                    {{ $courses->links() }}
                </div>
            @endif
        @endif
    </div>

</x-app-layout>
