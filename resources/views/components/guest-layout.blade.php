@props([
    'title' => 'Sign in',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · AcadVault</title>
    @vite(['resources/css/app.scss', 'resources/js/app.js'])
</head>
<body>
    <div class="av-progress" aria-hidden="true"></div>

    <div class="av-auth">

        {{-- Left, from 992px up: what AcadVault is, told through its three
             guarantees. The record illustration is decorative; the headline
             and feature list carry the same message as text. --}}
        <aside class="av-auth-showcase" aria-label="About AcadVault">
            <div class="av-auth-showcase-bg" aria-hidden="true">
                <span class="av-auth-orb av-auth-orb-gold"></span>
                <span class="av-auth-orb av-auth-orb-blue"></span>
            </div>

            <div class="av-auth-lockup">
                <span class="av-auth-lockup-mark">
                    <x-brand-mark :size="30" />
                </span>
                <span>
                    <span class="av-auth-lockup-name">AcadVault</span>
                    <span class="av-auth-lockup-sub">CCE Records</span>
                </span>
            </div>

            <div class="av-auth-pitch">
                <p class="av-auth-eyebrow">
                    {{ \App\Enums\Program::COLLEGE }} &middot; {{ \App\Enums\Program::UNIVERSITY }}
                </p>
                <h1 class="av-auth-headline">
                    Every grade encrypted.<br>
                    Every document <span class="av-auth-headline-accent">verifiable.</span>
                </h1>
                <p class="av-auth-lede">
                    The college's academic records, sealed in the database, opened only
                    for the right people, and issued as documents anyone can check with a scan.
                </p>
            </div>

            {{-- The encryption story in one picture: the same record as the
                 database holds it (behind) and as an authorised user sees it
                 (in front), then sealed as a verified document. --}}
            <div class="av-auth-demo" data-av-demo aria-hidden="true">
                <div class="av-demo-card av-demo-cipher">
                    <div class="av-demo-tag"><i class="bi bi-database-lock"></i> In the database</div>
                    <div class="av-demo-cipher-line">grade: eyJpdiI6IkZ4c1BnM3VR…</div>
                    <div class="av-demo-cipher-line">remarks: eyJpdiI6Ik1rUjlLd0NY…</div>
                </div>

                <div class="av-demo-card av-demo-record">
                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <div>
                            <div class="av-demo-tag av-demo-tag-light"><i class="bi bi-unlock"></i> What an authorised user sees</div>
                            <div class="av-demo-title">Academic Record</div>
                            <div class="av-demo-course">CS 101 &middot; Introduction to Computing</div>
                        </div>
                        <div class="av-demo-qr">
                            <i class="bi bi-qr-code"></i>
                            <span class="av-demo-qr-scan"></span>
                        </div>
                    </div>

                    <div class="av-demo-fields">
                        <div>
                            <div class="av-demo-label">Grade</div>
                            <div class="av-demo-value" data-av-decrypt>1.25</div>
                        </div>
                        <div>
                            <div class="av-demo-label">Remarks</div>
                            <div class="av-demo-value" data-av-decrypt>Passed</div>
                        </div>
                        <div class="av-demo-seal">
                            <i class="bi bi-patch-check-fill"></i> Verified
                        </div>
                    </div>
                </div>
            </div>

            <ul class="av-auth-features">
                <li><i class="bi bi-shield-lock" aria-hidden="true"></i><span>AES-256 encryption at rest</span></li>
                <li><i class="bi bi-person-badge" aria-hidden="true"></i><span>Role-based access</span></li>
                <li><i class="bi bi-qr-code-scan" aria-hidden="true"></i><span>QR-verified exports</span></li>
                <li><i class="bi bi-clipboard-check" aria-hidden="true"></i><span>Complete audit trail</span></li>
            </ul>

            <p class="av-auth-showcase-foot">&copy; {{ date('Y') }} AcadVault &middot; Academic Records Management System</p>
        </aside>

        {{-- Right: the form on the cream ground the logo artwork was drawn
             for. Below 992px this is the whole page. --}}
        <main class="av-auth-main">
            <div class="av-auth-form">
                <img src="{{ asset('images/logo-full.png') }}"
                     alt="AcadVault — Secure, Verified, Trusted. Academic Records Management System."
                     class="av-auth-logo">

                {{-- The showcase names the college on wide screens. --}}
                <div class="av-auth-college d-lg-none">
                    <i class="bi bi-cpu" aria-hidden="true"></i>
                    <span>
                        <span class="av-auth-college-name">{{ \App\Enums\Program::COLLEGE }}</span>
                        <span class="av-auth-college-university">{{ \App\Enums\Program::UNIVERSITY }}</span>
                    </span>
                </div>

                <div class="av-auth-card {{ $errors->any() ? 'av-auth-card-error' : '' }}">
                    {{ $slot }}
                </div>
            </div>
        </main>

    </div>
</body>
</html>
