@php
    $config = match ($status) {
        'authentic' => [
            'badge' => 'Authentic',
            'heroClass' => 'av-verify-authentic',
            'badgeClass' => 'badge-authentic',
            'icon' => 'bi-patch-check-fill',
            'headline' => 'This document is authentic',
            'blurb' => 'The fingerprint recorded when this document was issued still matches the record held in AcadVault. Nothing has been altered.',
        ],
        'tampered' => [
            'badge' => 'Tampered',
            'heroClass' => 'av-verify-tampered',
            'badgeClass' => 'badge-tampered',
            'icon' => 'bi-exclamation-octagon-fill',
            'headline' => 'This document does not match our records',
            'blurb' => 'The record held in AcadVault no longer matches the fingerprint taken when this document was issued. Do not accept this document — contact the registrar.',
        ],
        default => [
            'badge' => 'Invalid',
            'heroClass' => 'av-verify-tampered',
            'badgeClass' => 'badge-tampered',
            'icon' => 'bi-question-octagon-fill',
            'headline' => 'This document could not be found',
            'blurb' => 'No issued document matches this verification code. It may have been mistyped, or the document may never have been issued by AcadVault.',
        ],
    };
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Document verification · AcadVault</title>
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
            <span class="text-white-50 small ms-2 d-none d-sm-inline">{{ \App\Enums\Program::COLLEGE }} &middot; Document verification</span>
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

                        {{-- Colour is always paired with a text label --}}
                        <div class="mb-3">
                            <span class="badge badge-status {{ $config['badgeClass'] }} fs-6 px-3 py-2">
                                <i class="bi {{ $config['icon'] }}" aria-hidden="true"></i>
                                {{ $config['badge'] }}
                            </span>
                        </div>

                        <h1 class="h4 fw-bold mb-2">{{ $config['headline'] }}</h1>
                        <p class="text-body-secondary mb-0 mx-auto" style="max-width:34rem;">
                            {{ $config['blurb'] }}
                        </p>
                    </div>
                </div>

                @if ($export && $record)
                    {{-- ---------- Document contents ---------- --}}
                    <div class="card border-0 mb-4">
                        <div class="card-header bg-white">
                            <i class="bi bi-file-earmark-text text-accent me-2" aria-hidden="true"></i>
                            Document contents
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-sm-6">
                                    <dl class="av-kv mb-0">
                                        <dt>Student</dt>
                                        <dd>{{ $record->student?->user?->name ?? '—' }}</dd>

                                        <dt>Student number</dt>
                                        <dd><span class="av-hash">{{ $record->student?->student_number ?? '—' }}</span></dd>

                                        <dt>Program</dt>
                                        <dd>{{ $record->student?->program ?? '—' }}</dd>
                                    </dl>
                                </div>
                                <div class="col-sm-6">
                                    <dl class="av-kv mb-0">
                                        <dt>Course</dt>
                                        <dd>
                                            {{ $record->course?->code }}
                                            <div class="small text-body-secondary fw-normal">
                                                {{ $record->course?->title }}
                                            </div>
                                        </dd>

                                        <dt>Grade</dt>
                                        <dd class="fs-5 fw-bold">{{ $record->grade }}</dd>

                                        <dt>Remarks</dt>
                                        <dd>{{ $record->remarks ?? '—' }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ---------- Fingerprint evidence ---------- --}}
                    <div class="card border-0">
                        <div class="card-header bg-white">
                            <i class="bi bi-fingerprint text-accent me-2" aria-hidden="true"></i>
                            Verification detail
                        </div>
                        <div class="card-body">
                            <dl class="av-kv mb-0">
                                <dt>Document ID</dt>
                                <dd><span class="av-hash">{{ $export->uuid }}</span></dd>

                                <dt>Issued</dt>
                                <dd>
                                    {{ $export->created_at?->format('d M Y, H:i') }}
                                    by {{ $export->exporter?->name ?? 'System' }}
                                </dd>

                                <dt>Fingerprint at issue</dt>
                                <dd><span class="av-hash">{{ $expectedHash }}</span></dd>

                                <dt>Fingerprint now</dt>
                                <dd>
                                    <span class="av-hash {{ $status === 'authentic' ? '' : 'text-danger fw-bold' }}">
                                        {{ $actualHash }}
                                    </span>
                                </dd>
                            </dl>

                            @if ($status !== 'authentic')
                                <div class="alert alert-danger d-flex align-items-start gap-2 mt-3 mb-0" role="alert">
                                    <i class="bi bi-exclamation-triangle-fill mt-1" aria-hidden="true"></i>
                                    <div>
                                        <strong>The two fingerprints differ.</strong>
                                        The academic record has changed since this document was issued.
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <p class="text-center text-body-secondary small mt-4 mb-0">
                    <i class="bi bi-shield-lock me-1" aria-hidden="true"></i>
                    Verification is public and does not require an account. Every check is logged.
                </p>

            </div>
        </div>
    </div>

</body>
</html>
