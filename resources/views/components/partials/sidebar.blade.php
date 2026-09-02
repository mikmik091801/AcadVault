@props([
    'role' => null,
])

@php
    // Route::has() guards let the nav grow as later steps register their routes.
    $item = function (string $route, string $label, string $icon, array $activeOn = []) {
        if (! \Illuminate\Support\Facades\Route::has($route)) {
            return null;
        }

        $patterns = $activeOn ?: [str_replace('.index', '.*', $route)];

        return [
            'url' => route($route),
            'label' => $label,
            'icon' => $icon,
            'active' => request()->routeIs(...$patterns),
        ];
    };

    $sections = [];

    $sections['Overview'] = array_filter([
        $item('dashboard', 'Dashboard', 'bi-speedometer2', ['dashboard']),
    ]);

    $sections['Records'] = array_filter(match ($role) {
        \App\Enums\Role::Admin, \App\Enums\Role::Registrar => [
            $item('students.index', 'Students', 'bi-mortarboard'),
            $item('courses.index', 'Courses', 'bi-journal-bookmark'),
            $item('records.index', 'Academic Records', 'bi-file-earmark-text'),
            $item('drop-requests.index', 'Drop Requests', 'bi-hourglass-split'),
            $item('exports.index', 'Exports', 'bi-file-earmark-pdf'),
        ],
        \App\Enums\Role::Faculty => [
            $item('courses.index', 'My Courses', 'bi-journal-bookmark'),
            $item('records.index', 'Academic Records', 'bi-file-earmark-text'),
        ],
        \App\Enums\Role::Student => [
            $item('enrollments.index', 'My Classes', 'bi-journal-bookmark'),
            $item('records.index', 'My Records', 'bi-file-earmark-text'),
            $item('exports.index', 'My Exports', 'bi-file-earmark-pdf'),
        ],
        default => [],
    });

    $sections['Administration'] = array_filter(match ($role) {
        \App\Enums\Role::Admin => [
            $item('users.index', 'Users', 'bi-people'),
            $item('audit-logs.index', 'Audit Logs', 'bi-clipboard-data'),
        ],
        default => [],
    });

    $sections['Account'] = array_filter([
        $item('profile.edit', 'Profile', 'bi-person-gear', ['profile.*']),
    ]);
@endphp

<aside class="av-sidebar" id="avSidebar">
    <a href="{{ route('dashboard') }}" class="av-brand">
        <span class="av-brand-mark">
            <x-brand-mark :size="24" class="text-white" />
        </span>
        <span>
            <span class="av-brand-name d-block">AcadVault</span>
            <span class="av-brand-sub">Records System</span>
        </span>
    </a>

    <nav class="av-nav" aria-label="Main navigation">
        @foreach ($sections as $heading => $items)
            @continue(empty($items))

            <div class="av-nav-section">{{ $heading }}</div>

            @foreach ($items as $navItem)
                <a href="{{ $navItem['url'] }}"
                   class="av-nav-link {{ $navItem['active'] ? 'active' : '' }}"
                   @if ($navItem['active']) aria-current="page" @endif>
                    <i class="bi {{ $navItem['icon'] }}" aria-hidden="true"></i>
                    <span>{{ $navItem['label'] }}</span>
                </a>
            @endforeach
        @endforeach
    </nav>

    <div class="av-sidebar-footer">
        <i class="bi bi-shield-lock me-1" aria-hidden="true"></i>
        Encrypted &amp; audited
    </div>
</aside>
