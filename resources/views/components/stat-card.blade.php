@props([
    'label' => '',
    'value' => 0,
    'icon' => 'bi-bar-chart',
    'variant' => 'navy',   // navy | accent | success | warning | danger
    'hint' => null,
    'href' => null,
])

<div class="card stat-card border-0 h-100">
    <div class="card-body d-flex align-items-center gap-3">
        <span class="stat-icon stat-icon-{{ $variant }}">
            <i class="bi {{ $icon }}" aria-hidden="true"></i>
        </span>

        <div class="min-width-0">
            <div class="stat-label">{{ $label }}</div>
            <div class="stat-value">{{ $value }}</div>
            @if ($hint)
                <div class="small text-body-secondary mt-1">{{ $hint }}</div>
            @endif
        </div>
    </div>

    @if ($href)
        <a href="{{ $href }}" class="stretched-link" aria-label="View {{ $label }}"></a>
    @endif
</div>
