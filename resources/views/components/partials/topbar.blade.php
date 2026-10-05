@props([
    'user' => null,
    'title' => '',
])

@php
    $initials = collect(explode(' ', trim($user?->name ?? '?')))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

<header class="av-topbar">
    <button type="button"
            class="btn btn-light border d-lg-none"
            data-av-sidebar-toggle
            aria-label="Toggle navigation">
        <i class="bi bi-list" aria-hidden="true"></i>
    </button>

    {{-- Which college this workspace belongs to; the page itself carries its
         own title in the header below. --}}
    <div class="flex-grow-1 min-width-0">
        <div class="av-topbar-context">
            <span class="av-topbar-context-icon" aria-hidden="true"><i class="bi bi-cpu"></i></span>
            <span class="min-width-0">
                <span class="av-topbar-college">{{ \App\Enums\Program::COLLEGE }}</span>
                <span class="av-topbar-university d-none d-sm-block">{{ \App\Enums\Program::UNIVERSITY }}</span>
            </span>
        </div>
    </div>

    <div class="dropdown">
        <button class="av-user-chip dropdown-toggle" type="button"
                data-bs-toggle="dropdown" aria-expanded="false">
            <span class="av-avatar">{{ $initials ?: '?' }}</span>
            <span class="text-start d-none d-sm-block">
                <span class="av-user-name d-block">{{ $user?->name }}</span>
                <span class="av-user-role d-block">{{ $user?->role?->label() ?? 'User' }}</span>
            </span>
        </button>

        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
            <li class="px-3 py-2 d-sm-none">
                <div class="fw-semibold">{{ $user?->name }}</div>
                <div class="small text-body-secondary">{{ $user?->role?->label() ?? 'User' }}</div>
            </li>
            <li class="px-3 pb-2 pt-1 d-none d-sm-block">
                <div class="small text-body-secondary text-truncate">{{ $user?->email }}</div>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item" href="{{ route('profile.edit') }}">
                    <i class="bi bi-person-gear me-2" aria-hidden="true"></i>Profile
                </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="dropdown-item text-danger">
                        <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Log out
                    </button>
                </form>
            </li>
        </ul>
    </div>
</header>
