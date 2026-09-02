@props([
    'action' => '',
])

@php
    $meta = \App\Support\AuditLogger::describe($action);

    $class = match ($meta['variant']) {
        'success' => 'badge-authentic',
        'danger' => 'badge-tampered',
        'warning' => 'badge-warning-soft',
        default => 'badge-pending',
    };
@endphp

<span class="badge badge-status {{ $class }}" title="{{ $action }}">
    <i class="bi {{ $meta['icon'] }}" aria-hidden="true"></i>
    {{ $meta['label'] }}
</span>
