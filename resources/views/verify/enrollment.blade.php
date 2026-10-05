@php
    // "outdated" is deliberately not "tampered". A certificate lists the
    // enrollment as it stood when issued, and enrollment legitimately changes —
    // that is a superseded document, not a forged one.
    $config = match ($status) {
        'authentic' => [
            'badge' => 'Current',
            'heroClass' => 'av-verify-authentic',
            'badgeClass' => 'badge-authentic',
            'icon' => 'bi-patch-check-fill',
            'headline' => 'This certificate is current',
            'blurb' => 'The fingerprint recorded when this certificate was issued still matches the student\'s enrollment in AcadVault. The class list below is accurate.',
        ],
        'outdated' => [
            'badge' => 'Superseded',
            'heroClass' => 'av-verify-tampered',
            'badgeClass' => 'badge-warning-soft',
            'icon' => 'bi-clock-history',
            'headline' => 'This certificate is out of date',
            'blurb' => 'This certificate was genuinely issued by AcadVault, but the student\'s enrollment has changed since — a class was added, or a drop was approved. The list below is their enrollment as it stands now. Ask for a freshly issued certificate.',
        ],
        default => [
            'badge' => 'Invalid',
            'heroClass' => 'av-verify-tampered',
            'badgeClass' => 'badge-tampered',
            'icon' => 'bi-question-octagon-fill',
            'headline' => 'This certificate could not be found',
            'blurb' => 'No issued certificate matches this verification code. It may have been mistyped, or the certificate may never have been issued by AcadVault.',
        ],
    };
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Certificate verification · AcadVault</title>
    @vite(['resources/css/app.scss', 'resources/js/app.js'])
</head>
<body>

    {{-- Slim public header --}}
    <div class="bg-navy py-3 mb-4">
        <div class="container d-flex align-items-center gap-2">
            <span class="av-brand-mark" style="width:36px;height:36px;">
                <x-brand-mark :size="24" />
            </span>
            <span class="text-white fw-bold fs-5">AcadVault</span>
            <span class="text-white-50 small ms-2 d-none d-sm-inline">{{ \App\Enums\Program::COLLEGE }} &middot; Certificate verification</span>
        </div>
    </div>

    <div class="container pb-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                {{-- ---------- Result ---------- --}}
                <div class="card border-0 mb-4">
                    <div class="card-body text-center p-5">
                        <div class="av-verify-hero {{ $config['heroClass'] }} mx-auto">
                            <i class="bi {{ $config['icon'] }}" aria-hidden="true"></i>
                        </div>

                        <span class="badge badge-status {{ $config['badgeClass'] }} mb-3">
                            {{ $config['badge'] }}
                        </span>

                        <h1 class="h3 fw-bold">{{ $config['headline'] }}</h1>
                        <p class="text-body-secondary mb-0">{{ $config['blurb'] }}</p>
                    </div>
                </div>

                @if ($document)
                    {{-- ---------- Student ---------- --}}
                    <div class="card border-0 mb-4">
                        <div class="card-header bg-white">
                            <i class="bi bi-person-vcard text-accent me-2" aria-hidden="true"></i>
                            Student
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="text-body-secondary small text-uppercase">Name</div>
                                    <div class="fw-semibold">{{ $document->student?->user?->name }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-body-secondary small text-uppercase">Student number</div>
                                    <div class="av-hash">{{ $document->student?->student_number }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-body-secondary small text-uppercase">Program</div>
                                    <div class="fw-semibold">{{ $document->student?->program }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-body-secondary small text-uppercase">Year level</div>
                                    <div class="fw-semibold">{{ $document->student?->yearLevelLabel() }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ---------- Classes ---------- --}}
                    <div class="card border-0 mb-4">
                        <div class="card-header bg-white">
                            <i class="bi bi-journal-bookmark text-accent me-2" aria-hidden="true"></i>
                            {{ $status === 'authentic' ? 'Enrolled classes' : 'Enrolled classes today' }}
                            <span class="badge badge-status badge-pending ms-1">{{ $classes->count() }}</span>
                        </div>

                        @if ($classes->isEmpty())
                            <x-empty-state icon="bi-journal" title="No enrolled classes"
                                message="This student currently holds no classes." />
                        @else
                            <div class="table-responsive">
                                <table class="table av-table table-striped align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th scope="col">Code</th>
                                            <th scope="col">Course title</th>
                                            <th scope="col">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($classes as $enrollment)
                                            <tr>
                                                <td><code class="av-hash">{{ $enrollment->course?->code }}</code></td>
                                                <td>{{ $enrollment->course?->title }}</td>
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
                    </div>

                    {{-- ---------- Verification detail ---------- --}}
                    <div class="card border-0">
                        <div class="card-header bg-white">
                            <i class="bi bi-fingerprint text-accent me-2" aria-hidden="true"></i>
                            Verification detail
                        </div>
                        <div class="card-body">
                            <dl class="av-kv mb-0">
                                <dt>Document ID</dt>
                                <dd><span class="av-hash">{{ $document->uuid }}</span></dd>

                                <dt>Issued</dt>
                                <dd>
                                    {{ $document->created_at?->format('d M Y, H:i') }}
                                    @if ($document->issuer)
                                        by {{ $document->issuer->name }}
                                    @endif
                                </dd>

                                <dt>Classes when issued</dt>
                                <dd>{{ $document->class_count }}</dd>

                                <dt>Fingerprint at issue</dt>
                                <dd><span class="av-hash">{{ $expectedHash }}</span></dd>

                                <dt>Fingerprint now</dt>
                                <dd><span class="av-hash">{{ $actualHash }}</span></dd>
                            </dl>
                        </div>
                    </div>
                @endif

                <p class="text-center text-body-secondary small mt-4 mb-0">
                    <i class="bi bi-shield-check me-1" aria-hidden="true"></i>
                    Every verification attempt is recorded in the AcadVault audit log.
                </p>

            </div>
        </div>
    </div>

</body>
</html>
