@php
    $hasFilters = $search !== '' || $role !== '';
@endphp

<x-app-layout title="Users">

    <x-page-header
        title="Users"
        subtitle="Every account in the system. Roles can only be changed here."
        icon="bi-people">
        <x-slot:actions>
            @can('create', App\Models\User::class)
                <a href="{{ route('users.create') }}" class="btn btn-accent">
                    <i class="bi bi-person-plus me-1" aria-hidden="true"></i>Add user
                </a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    {{-- Summary — each card doubles as a role filter --}}
    <div class="row g-3 mb-4">
        @foreach ($stats as $stat)
            <div class="col-6 col-xl-3">
                <x-stat-card
                    :label="$stat['label']"
                    :value="$stat['value']"
                    :icon="$stat['icon']"
                    :variant="$stat['variant']"
                    :href="$stat['href']" />
            </div>
        @endforeach
    </div>

    <div class="card border-0">
        <div class="card-header bg-white">
            <form method="GET" action="{{ route('users.index') }}">
                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label for="search" class="form-label">Search</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white text-body-secondary">
                                <i class="bi bi-search" aria-hidden="true"></i>
                            </span>
                            <input id="search" type="search" name="search" value="{{ $search }}"
                                   class="form-control" placeholder="Name or email…">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label for="role" class="form-label">Role</label>
                        <select id="role" name="role" class="form-select">
                            <option value="">All roles</option>
                            @foreach (App\Enums\Role::cases() as $case)
                                <option value="{{ $case->value }}" @selected($role === $case->value)>
                                    {{ $case->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 d-flex justify-content-end gap-2">
                        @if ($hasFilters)
                            <a href="{{ route('users.index') }}" class="btn btn-light">
                                <i class="bi bi-x-lg me-1" aria-hidden="true"></i>Clear
                            </a>
                        @endif
                        <button type="submit" class="btn btn-primary flex-grow-1 flex-md-grow-0">
                            <i class="bi bi-funnel me-1" aria-hidden="true"></i>Filter
                        </button>
                    </div>
                </div>
            </form>

            <div class="text-body-secondary small mt-3">
                {{ $users->total() }} {{ Str::plural('account', $users->total()) }}
                {{ $hasFilters ? 'matching your filters' : 'in total' }}
            </div>
        </div>

        @if ($users->isEmpty())
            <x-empty-state icon="bi-people"
                title="{{ $hasFilters ? 'No users match your filters' : 'No users yet' }}"
                message="{{ $hasFilters ? 'Try a different name, email or role.' : 'Add an account to get started.' }}" />
        @else
            <div class="table-responsive">
                <table class="table av-table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Role</th>
                            <th scope="col">Student number</th>
                            <th scope="col">Joined</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $account)
                            <tr>
                                <td>
                                    <div class="fw-semibold">
                                        {{ $account->name }}
                                        @if ($account->is(auth()->user()))
                                            <span class="badge badge-status badge-pending ms-1">You</span>
                                        @endif
                                    </div>
                                    <div class="small text-body-secondary">{{ $account->email }}</div>
                                </td>
                                <td><x-role-badge :role="$account->role" /></td>
                                <td>
                                    @if ($account->student)
                                        <code class="av-hash">{{ $account->student->student_number }}</code>
                                    @else
                                        <span class="text-body-secondary">—</span>
                                    @endif
                                </td>
                                <td class="text-body-secondary text-nowrap">
                                    {{ $account->created_at?->format('d M Y') }}
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('users.show', $account) }}"
                                           class="btn btn-outline-secondary" title="View">
                                            <i class="bi bi-eye" aria-hidden="true"></i>
                                            <span class="visually-hidden">View {{ $account->name }}</span>
                                        </a>
                                        @can('update', $account)
                                            <a href="{{ route('users.edit', $account) }}"
                                               class="btn btn-outline-secondary" title="Edit">
                                                <i class="bi bi-pencil" aria-hidden="true"></i>
                                                <span class="visually-hidden">Edit {{ $account->name }}</span>
                                            </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div class="card-body border-top">
                    {{ $users->links() }}
                </div>
            @endif
        @endif
    </div>

</x-app-layout>
