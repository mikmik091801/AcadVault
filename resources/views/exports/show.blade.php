<x-app-layout title="Issued document">

    <x-page-header
        title="Issued document"
        subtitle="{{ $export->academicRecord?->student?->user?->name }} · {{ $export->academicRecord?->course?->code }}"
        icon="bi-file-earmark-pdf">
        <x-slot:actions>
            <a href="{{ route('exports.index') }}" class="btn btn-light">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back
            </a>
            <a href="{{ route('exports.download', $export) }}" class="btn btn-accent">
                <i class="bi bi-download me-1" aria-hidden="true"></i>Download PDF
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 h-100">
                <div class="card-header bg-white">
                    <i class="bi bi-card-checklist text-accent me-2" aria-hidden="true"></i>Document contents
                </div>
                <div class="card-body">
                    <dl class="av-kv mb-0">
                        <dt>Student</dt>
                        <dd>
                            {{ $export->academicRecord?->student?->user?->name }}
                            <div class="small text-body-secondary fw-normal">
                                {{ $export->academicRecord?->student?->student_number }}
                            </div>
                        </dd>

                        <dt>Course</dt>
                        <dd>
                            {{ $export->academicRecord?->course?->code }}
                            <div class="small text-body-secondary fw-normal">
                                {{ $export->academicRecord?->course?->title }}
                            </div>
                        </dd>

                        <dt>Grade</dt>
                        <dd class="fs-5 fw-bold">{{ $export->academicRecord?->grade }}</dd>

                        <dt>Issued</dt>
                        <dd>
                            {{ $export->created_at?->format('d M Y, H:i') }}
                            by {{ $export->exporter?->name ?? 'System' }}
                        </dd>

                        <dt>SHA-256 fingerprint at issue</dt>
                        <dd><span class="av-hash">{{ $export->file_hash }}</span></dd>
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
                    <x-qr :url="$verifyUrl" />

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
                </div>
            </div>
        </div>
    </div>

</x-app-layout>
