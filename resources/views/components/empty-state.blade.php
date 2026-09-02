@props([
    'icon' => 'bi-inbox',
    'title' => 'Nothing here yet',
    'message' => null,
])

<div class="av-empty">
    <i class="bi {{ $icon }}" aria-hidden="true"></i>
    <div class="fw-semibold" style="color:var(--av-navy-900);">{{ $title }}</div>
    @if ($message)
        <p class="mb-0 mt-1">{{ $message }}</p>
    @endif

    @if (isset($action))
        <div class="mt-3">{{ $action }}</div>
    @endif
</div>
