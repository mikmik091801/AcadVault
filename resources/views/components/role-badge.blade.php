@props([
    'role' => null,
])

@if ($role)
    <span class="badge badge-status badge-role">
        <i class="bi {{ $role->icon() }}" aria-hidden="true"></i>
        {{ $role->label() }}
    </span>
@else
    <span class="badge badge-status badge-pending">
        <i class="bi bi-dash-circle" aria-hidden="true"></i>
        Unassigned
    </span>
@endif
