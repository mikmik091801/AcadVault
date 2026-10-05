<x-app-layout title="Students">

    <x-page-header
        title="Students"
        subtitle="Student profiles linked to user accounts."
        icon="bi-mortarboard">
        <x-slot:actions>
            @can('create', App\Models\Student::class)
                <a href="{{ route('students.create') }}" class="btn btn-accent">
                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add student
                </a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="card border-0">
        <div class="card-header bg-white">
            <div class="row g-2 align-items-center">
                <div class="col-md-6">
                    <span class="text-body-secondary small">
                        {{ $students->total() }} {{ Str::plural('student', $students->total()) }} found
                    </span>
                </div>
                <div class="col-md-6">
                    <x-search-box :value="$search" placeholder="Search name, number or program…" />
                </div>
            </div>
        </div>

        @if ($students->isEmpty())
            <x-empty-state icon="bi-mortarboard"
                title="{{ $search !== '' ? 'No students match your search' : 'No students yet' }}"
                message="{{ $search !== '' ? 'Try a different name, student number or program.' : 'Add a student profile to get started.' }}" />
        @else
            <div class="table-responsive">
                <table class="table av-table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Student</th>
                            <th scope="col">Student number</th>
                            <th scope="col">Program</th>
                            <th scope="col">Year</th>
                            <th scope="col">Records</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($students as $student)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $student->user?->name }}</div>
                                    <div class="small text-body-secondary">{{ $student->user?->email }}</div>
                                </td>
                                <td><code class="av-hash">{{ $student->student_number }}</code></td>
                                <td>
                                    <span class="badge badge-status badge-role" title="{{ $student->program }}">
                                        {{ \App\Enums\Program::shortNameFor($student->program) }}
                                    </span>
                                </td>
                                <td>{{ $student->yearLevelLabel() }}</td>
                                <td>
                                    <span class="badge badge-status badge-pending">
                                        {{ $student->academic_records_count }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('students.show', $student) }}"
                                           class="btn btn-outline-secondary" title="View">
                                            <i class="bi bi-eye" aria-hidden="true"></i>
                                            <span class="visually-hidden">View {{ $student->user?->name }}</span>
                                        </a>
                                        @can('update', $student)
                                            <a href="{{ route('students.edit', $student) }}"
                                               class="btn btn-outline-secondary" title="Edit">
                                                <i class="bi bi-pencil" aria-hidden="true"></i>
                                                <span class="visually-hidden">Edit {{ $student->user?->name }}</span>
                                            </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($students->hasPages())
                <div class="card-body border-top">
                    {{ $students->links() }}
                </div>
            @endif
        @endif
    </div>

</x-app-layout>
