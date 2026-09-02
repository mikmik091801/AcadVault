<x-app-layout title="Certificate of registration">

    <x-page-header
        title="Certificate of registration"
        subtitle="{{ $document->student?->user?->name }} · {{ $document->student?->student_number }}"
        icon="bi-file-earmark-check">
        <x-slot:actions>
            <a href="{{ route('enrollments.index') }}" class="btn btn-light">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back
            </a>
            <a href="{{ route('enrollment-documents.download', $document) }}" class="btn btn-accent">
                <i class="bi bi-download me-1" aria-hidden="true"></i>Download PDF
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 h-100">
                <div class="card-header bg-white">
                    <i class="bi bi-card-checklist text-accent me-2" aria-hidden="true"></i>
                    Classes on this certificate
                    <span class="badge badge-status badge-pending ms-1">{{ $classes->count() }}</span>
                </div>

                @if ($classes->isEmpty())
                    <x-empty-state icon="bi-journal" title="No classes"
                        message="This student holds no enrolled classes." />
                @else
                    <div class="table-responsive">
                        <table class="table av-table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Code</th>
                                    <th scope="col">Course</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($classes as $enrollment)
                                    <tr>
                                        <td><code class="av-hash">{{ $enrollment->course?->code }}</code></td>
                                        <td>
                                            <div class="fw-semibold">{{ $enrollment->course?->title }}</div>
                                            <div class="small text-body-secondary">
                                                {{ $enrollment->course?->faculty?->name ?? 'Unassigned' }}
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-status {{ $enrollment->status->badgeClass() }}">
                                                {{ $enrollment->status->label() }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <div class="card-body border-top">
                    <dl class="av-kv mb-0">
                        <dt>Document ID</dt>
                        <dd><span class="av-hash">{{ $document->uuid }}</span></dd>

                        <dt>Issued</dt>
                        <dd>
                            {{ $document->created_at?->format('d M Y, H:i') }}
                            by {{ $document->issuer?->name ?? 'System' }}
                        </dd>

                        <dt>Classes at issue</dt>
                        <dd>{{ $document->class_count }}</dd>

                        <dt>SHA-256 fingerprint at issue</dt>
                        <dd><span class="av-hash">{{ $document->file_hash }}</span></dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 h-100">
                <div class="card-header bg-white">
                    <i class="bi bi-qr-code text-accent me-2" aria-hidden="true"></i>Verification code
                </div>
                <div class="card-body text-center">
                    @if ($document->qr_code_path)
                        <img src="{{ Storage::disk('public')->url($document->qr_code_path) }}"
                             alt="QR code linking to the public verification page"
                             class="img-fluid mb-3" style="max-width:210px;">
                    @endif

                    <p class="text-body-secondary small mb-3">
                        This code is printed on the PDF. Scanning it opens the public
                        verification page — no account needed.
                    </p>

                    <a href="{{ $verifyUrl }}" target="_blank" rel="noopener"
                       class="btn btn-outline-accent btn-sm">
                        <i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i>
                        Open verification page
                    </a>

                    <div class="av-hash mt-3">{{ $verifyUrl }}</div>

                    <div class="alert alert-light border small text-start mt-3 mb-0">
                        <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                        Enrolling in another class — or having a drop approved — changes
                        the fingerprint, so this certificate will then read as
                        superseded. Issue a fresh one after any change.
                    </div>
                </div>
            </div>
        </div>
    </div>

</x-app-layout>
