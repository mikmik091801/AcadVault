@props([
    'title' => 'Dashboard',
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
    @php
        $user = auth()->user();
        $role = $user?->role;
    @endphp

    <div class="av-sidebar-backdrop" aria-hidden="true"></div>

    <x-partials.sidebar :role="$role" />

    <div class="av-content">
        <x-partials.topbar :user="$user" :title="$title" />

        <main class="av-page">
            <div class="container-fluid px-0">
                {{ $slot }}
            </div>
        </main>

        <footer class="text-center text-body-secondary py-3" style="font-size:.78rem;">
            AcadVault &mdash; Academic Records Management System &middot;
            &copy; {{ date('Y') }}
        </footer>
    </div>

    <x-toasts />
</body>
</html>
