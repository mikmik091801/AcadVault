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
    {{-- Single centred column on the cream ground. The logo artwork is
         transparent, so it sits straight on the page with nothing behind it
         to box it in. --}}
    <div class="av-auth">
        <main class="av-auth-form">
            <img src="{{ asset('images/logo-full.png') }}"
                 alt="AcadVault — Secure, Verified, Trusted. Academic Records Management System."
                 class="av-auth-logo">

            <div class="av-auth-card">
                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>
