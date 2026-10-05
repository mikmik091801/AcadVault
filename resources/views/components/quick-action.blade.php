@props([
    'href' => '#',
    'icon' => 'bi-arrow-right',
    'title' => '',
    'description' => null,
])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'av-quick-action']) }}>
    <span class="av-quick-action-icon">
        <i class="bi {{ $icon }}" aria-hidden="true"></i>
    </span>
    <span class="min-width-0 flex-grow-1">
        <span class="av-quick-action-title">{{ $title }}</span>
        @if ($description)
            <span class="av-quick-action-desc">{{ $description }}</span>
        @endif
    </span>
    <i class="bi bi-arrow-right av-quick-action-arrow" aria-hidden="true"></i>
</a>
