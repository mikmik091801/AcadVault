@props([
    'value' => '',
    'placeholder' => 'Search…',
    'action' => null,
    'keep' => [],   // other query parameters (e.g. an active filter) to carry through a search
])

<form method="GET" action="{{ $action ?? url()->current() }}" class="d-flex gap-2" role="search">
    @foreach (array_filter($keep, fn ($kept) => $kept !== null && $kept !== '') as $name => $kept)
        <input type="hidden" name="{{ $name }}" value="{{ $kept }}">
    @endforeach

    <div class="input-group">
        <span class="input-group-text bg-white text-body-secondary">
            <i class="bi bi-search" aria-hidden="true"></i>
        </span>

        <input type="search" name="search" value="{{ $value }}"
               class="form-control" placeholder="{{ $placeholder }}"
               aria-label="{{ $placeholder }}">

        @if ($value !== '' && $value !== null)
            <a href="{{ url()->current().(array_filter($keep) ? '?'.http_build_query(array_filter($keep)) : '') }}"
               class="btn btn-outline-secondary" aria-label="Clear search">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </a>
        @endif
    </div>

    <button type="submit" class="btn btn-primary flex-shrink-0">Search</button>
</form>
