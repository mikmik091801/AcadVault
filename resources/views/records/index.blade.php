@php
    $user = auth()->user();
@endphp

<x-app-layout title="Academic records">

    <x-page-header
        title="{{ $user->isStudent() ? 'My academic records' : 'Academic records' }}"
        subtitle="{{ match(true) {
            $user->isStudent() => 'Your grades. Every view is written to the audit log.',
            $user->isFaculty() => 'Records for the courses you teach.',
            default => 'All academic records. Grades are encrypted at rest.',
        } }}"
        icon="bi-file-earmark-text">
        <x-slot:actions>
            @can('create', App\Models\AcademicRecord::class)
                <a href="{{ route('records.create') }}" class="btn btn-accent">
                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add record
                </a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="card border-0">
        <div class="card-header bg-white">
            <div class="row g-2 align-items-center">
                <div class="col-md-6">
                    <span class="text-body-secondary small">
                        {{ $records->total() }} {{ Str::plural('record', $records->total()) }} found
                    </span>
                </div>
                <div class="col-md-6">
                    <x-search-box :value="$search" placeholder="Search student or course…" />
                </div>
            </div>
        </div>

        @if ($records->isEmpty())
            <x-empty-state icon="bi-file-earmark"
                title="{{ $search !== '' ? 'No records match your search' : 'No records yet' }}"
                message="{{ $search !== '' ? 'Grades are encrypted, so only student and course details are searchable.' : 'Academic records will appear here once they are added.' }}" />
        @else
            <div class="table-responsive">
                <table class="table av-table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            @unless ($user->isStudent())
                                <th scope="col">Student</th>
                            @endunless
                            <th scope="col">Course</th>
                            <th scope="col">Grade</th>
                            <th scope="col">Remarks</th>
                            <th scope="col">Recorded</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($records as $record)
                            <tr>
                                @unless ($user->isStudent())
                                    <td>
                                        <div class="fw-semibold">{{ $record->student?->user?->name }}</div>
                                        <div class="small text-body-secondary">
                                            {{ $record->student?->student_number }}
                                        </div>
                                    </td>
                                @endunless
                                <td>
                                    <span class="fw-semibold">{{ $record->course?->code }}</span>
                                    <div class="small text-body-secondary">{{ $record->course?->title }}</div>
                                </td>
                                <td class="fw-semibold">{{ $record->grade }}</td>
                                <td class="text-body-secondary">{{ $record->remarks ?? '—' }}</td>
                                <td class="text-body-secondary">{{ $record->created_at?->format('d M Y') }}</td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('records.show', $record) }}"
                                           class="btn btn-outline-secondary" title="View">
                                            <i class="bi bi-eye" aria-hidden="true"></i>
                                            <span class="visually-hidden">View record</span>
                                        </a>
                                        @can('update', $record)
                                            <a href="{{ route('records.edit', $record) }}"
                                               class="btn btn-outline-secondary" title="Edit">
                                                <i class="bi bi-pencil" aria-hidden="true"></i>
                                                <span class="visually-hidden">Edit record</span>
                                            </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($records->hasPages())
                <div class="card-body border-top">
                    {{ $records->links() }}
                </div>
            @endif
        @endif
    </div>

</x-app-layout>
