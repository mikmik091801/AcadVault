@php
    $hasFilters = collect($filters)->contains(fn ($v) => $v !== '');
@endphp

<x-app-layout title="Audit logs">

    <x-page-header
        title="Audit logs"
        subtitle="Every sign-in, record view, export and blocked attempt. Entries are append-only."
        icon="bi-clipboard-data" />

    {{-- Summary --}}
    <div class="row g-3 mb-4">
        @foreach ($stats as $stat)
            <div class="col-6 col-xl-3">
                <x-stat-card
                    :label="$stat['label']"
                    :value="$stat['value']"
                    :icon="$stat['icon']"
                    :variant="$stat['variant']" />
            </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="card border-0 mb-4">
        <div class="card-header bg-white">
            <i class="bi bi-funnel text-accent me-2" aria-hidden="true"></i>Filter
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('audit-logs.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white text-body-secondary">
                                <i class="bi bi-search" aria-hidden="true"></i>
                            </span>
                            <input id="search" type="search" name="search" value="{{ $filters['search'] }}"
                                   class="form-control" placeholder="User, IP address or target…">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label for="action" class="form-label">Action</label>
                        <select id="action" name="action" class="form-select">
                            <option value="">All actions</option>
                            @foreach ($actions as $value => $label)
                                <option value="{{ $value }}" @selected($filters['action'] === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="user_id" class="form-label">User</label>
                        <select id="user_id" name="user_id" class="form-select">
                            <option value="">All users</option>
                            @foreach ($users as $option)
                                <option value="{{ $option->id }}" @selected($filters['user_id'] == $option->id)>
                                    {{ $option->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="from" class="form-label">From date</label>
                        <input id="from" type="date" name="from" value="{{ $filters['from'] }}" class="form-control">
                    </div>

                    <div class="col-md-3">
                        <label for="to" class="form-label">To date</label>
                        <input id="to" type="date" name="to" value="{{ $filters['to'] }}" class="form-control">
                    </div>

                    <div class="col-md-6 d-flex justify-content-end gap-2">
                        @if ($hasFilters)
                            <a href="{{ route('audit-logs.index') }}" class="btn btn-light">
                                <i class="bi bi-x-lg me-1" aria-hidden="true"></i>Clear
                            </a>
                        @endif
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-funnel me-1" aria-hidden="true"></i>Apply filters
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Results --}}
    <div class="card border-0">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="text-body-secondary small">
                {{ number_format($logs->total()) }} {{ Str::plural('entry', $logs->total()) }}
                {{ $hasFilters ? 'matching your filters' : 'recorded' }}
            </span>
        </div>

        @if ($logs->isEmpty())
            <x-empty-state icon="bi-clipboard"
                title="{{ $hasFilters ? 'No entries match these filters' : 'No audit entries yet' }}"
                message="{{ $hasFilters ? 'Try widening the date range or clearing the action filter.' : 'Activity will be recorded here as people use the system.' }}" />
        @else
            <div class="table-responsive">
                <table class="table av-table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Action</th>
                            <th scope="col">User</th>
                            <th scope="col">Target</th>
                            <th scope="col">IP address</th>
                            <th scope="col">When</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td><x-action-badge :action="$log->action" /></td>
                                <td>
                                    @if ($log->user)
                                        <div class="fw-semibold">{{ $log->user->name }}</div>
                                        <div class="small text-body-secondary">{{ $log->user->email }}</div>
                                    @else
                                        <span class="text-body-secondary fst-italic">Unauthenticated</span>
                                    @endif
                                </td>
                                <td class="text-body-secondary">
                                    <span class="av-hash">{{ $log->targetLabel() ?? '—' }}</span>
                                </td>
                                <td class="text-body-secondary">
                                    <span class="av-hash">{{ $log->ip_address ?? '—' }}</span>
                                </td>
                                <td class="text-body-secondary text-nowrap">
                                    {{ $log->created_at?->format('d M Y, H:i:s') }}
                                    <div class="small">{{ $log->created_at?->diffForHumans() }}</div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($logs->hasPages())
                <div class="card-body border-top">
                    {{ $logs->links() }}
                </div>
            @endif
        @endif
    </div>

</x-app-layout>
