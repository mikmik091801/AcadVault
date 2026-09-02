@props([
    'title' => '',
    'subtitle' => null,
    'icon' => null,
])

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="av-page-title">
            @if ($icon)
                <i class="bi {{ $icon }} text-accent me-1" aria-hidden="true"></i>
            @endif
            {{ $title }}
        </h1>
        @if ($subtitle)
            <p class="av-page-subtitle">{{ $subtitle }}</p>
        @endif
    </div>

    @if (isset($actions))
        <div class="d-flex flex-wrap gap-2">
            {{ $actions }}
        </div>
    @endif
</div>
