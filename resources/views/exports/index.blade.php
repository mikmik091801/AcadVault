@php
    $user = auth()->user();
@endphp

<x-app-layout title="Exports">

    <x-page-header
        title="{{ $user->isStudent() ? 'My exports' : 'Exports' }}"
        subtitle="QR-verified documents issued from academic records."
        icon="bi-file-earmark-pdf" />

    <div class="card border-0">
        <div class="card-header bg-white">
            <div class="row g-2 align-items-center">
                <div class="col-md-6">
                    <span class="text-body-secondary small">
                        {{ $exports->total() }} {{ Str::plural('document', $exports->total()) }} issued
                    </span>
                </div>
                <div class="col-md-6">
                    <x-search-box :value="$search" placeholder="Search student, course or document ID…" />
                </div>
            </div>
        </div>

        @if ($exports->isEmpty())
            <x-empty-state icon="bi-file-earmark-pdf"
                title="{{ $search !== '' ? 'No documents match your search' : 'No documents issued yet' }}"
                message="{{ $user->isStudent()
                    ? 'When the registrar issues a document for your records it will appear here.'
                    : 'Open an academic record and choose Export to issue a verifiable PDF.' }}" />
        @else
            <div class="table-responsive">
                <table class="table av-table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            @unless ($user->isStudent())
                                <th scope="col">Student</th>
                            @endunless
                            <th scope="col">Course</th>
                            <th scope="col">Document ID</th>
                            <th scope="col">Issued</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($exports as $export)
                            <tr>
                                @unless ($user->isStudent())
                                    <td>
                                        <div class="fw-semibold">
                                            {{ $export->academicRecord?->student?->user?->name ?? '—' }}
                                        </div>
                                        <div class="small text-body-secondary">
                                            {{ $export->academicRecord?->student?->student_number }}
                                        </div>
                                    </td>
                                @endunless
                                <td>
                                    <span class="fw-semibold">{{ $export->academicRecord?->course?->code }}</span>
                                    <div class="small text-body-secondary">
                                        {{ $export->academicRecord?->course?->title }}
                                    </div>
                                </td>
                                <td>
                                    <span class="av-hash">{{ Str::limit($export->uuid, 13) }}</span>
                                </td>
                                <td class="text-body-secondary">
                                    {{ $export->created_at?->format('d M Y') }}
                                    <div class="small">by {{ $export->exporter?->name ?? 'System' }}</div>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('exports.show', $export) }}"
                                           class="btn btn-outline-secondary" title="View">
                                            <i class="bi bi-eye" aria-hidden="true"></i>
                                            <span class="visually-hidden">View document</span>
                                        </a>
                                        <a href="{{ route('exports.download', $export) }}"
                                           class="btn btn-outline-secondary" title="Download PDF">
                                            <i class="bi bi-download" aria-hidden="true"></i>
                                            <span class="visually-hidden">Download PDF</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($exports->hasPages())
                <div class="card-body border-top">
                    {{ $exports->links() }}
                </div>
            @endif
        @endif
    </div>

</x-app-layout>
