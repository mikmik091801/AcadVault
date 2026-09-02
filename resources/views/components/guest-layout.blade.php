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
    <div class="av-auth container-fluid">
        <div class="row g-0 min-vh-100">

            {{-- Left: cream branded panel, shown from 992px up. The logo is
                 navy line art, so cream is the ground it was drawn for and it
                 needs no plate here — the lockup carries the wordmark and the
                 "Secure · Verified · Trusted" line itself. --}}
            <div class="col-lg-6 av-auth-brand d-none d-lg-flex">
                <div>
                    <img src="{{ asset('images/logo-full.png') }}"
                         alt="AcadVault — Secure, Verified, Trusted. Academic Records Management System."
                         class="av-auth-logo-full">

                    <p class="av-auth-tagline">
                        The academic records management system that keeps every grade
                        encrypted, every access logged, and every document verifiable.
                    </p>

                    <div class="av-auth-feature">
                        <i class="bi bi-shield-lock-fill" aria-hidden="true"></i>
                        <span>Grades and remarks are AES-256 encrypted at rest.</span>
                    </div>
                    <div class="av-auth-feature">
                        <i class="bi bi-person-badge-fill" aria-hidden="true"></i>
                        <span>Role-based access for admins, registrars, faculty and students.</span>
                    </div>
                    <div class="av-auth-feature">
                        <i class="bi bi-qr-code-scan" aria-hidden="true"></i>
                        <span>QR-verified PDF exports that prove a document is authentic.</span>
                    </div>
                    <div class="av-auth-feature">
                        <i class="bi bi-clipboard-check-fill" aria-hidden="true"></i>
                        <span>Every view, export and blocked attempt is written to an audit trail.</span>
                    </div>
                </div>
            </div>

            {{-- Right: navy form panel. Below 992px this is the whole page,
                 so the logo comes with it — on a cream plate, because the
                 artwork cannot read against navy. --}}
            <div class="col-lg-6 av-auth-form-panel">
                <div class="av-auth-form">
                    <div class="d-lg-none text-center mb-4">
                        <span class="av-auth-logo-plate">
                            <img src="{{ asset('images/logo-full.png') }}" alt="AcadVault"
                                 class="av-auth-logo-full">
                        </span>
                    </div>

                    {{ $slot }}
                </div>
            </div>

        </div>
    </div>
</body>
</html>
