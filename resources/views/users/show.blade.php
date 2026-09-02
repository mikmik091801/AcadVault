@php
    $isSelf = $user->is(auth()->user());
    $cascadedRecords = $user->student?->academic_records_count ?? 0;
@endphp

<x-app-layout title="User details">

    <x-page-header
        title="{{ $user->name }}"
        subtitle="{{ $user->email }}"
        icon="{{ $user->role->icon() }}">
        <x-slot:actions>
            <a href="{{ route('users.index') }}" class="btn btn-light">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back
            </a>
            @can('update', $user)
                <a href="{{ route('users.edit', $user) }}" class="btn btn-primary">
                    <i class="bi bi-pencil me-1" aria-hidden="true"></i>Edit
                </a>
            @endcan
            @can('delete', $user)
                <button type="button" class="btn btn-outline-danger"
                        data-bs-toggle="modal" data-bs-target="#deleteUserModal">
                    <i class="bi bi-trash3 me-1" aria-hidden="true"></i>Delete
                </button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 h-100">
                <div class="card-header bg-white">
                    <i class="bi bi-person-vcard text-accent me-2" aria-hidden="true"></i>Account
                </div>
                <div class="card-body">
                    <dl class="av-kv mb-0">
                        <dt>Full name</dt>
                        <dd>
                            {{ $user->name }}
                            @if ($isSelf)
                                <span class="badge badge-status badge-pending ms-1">You</span>
                            @endif
                        </dd>

                        <dt>Email</dt>
                        <dd>{{ $user->email }}</dd>

                        <dt>Role</dt>
                        <dd><x-role-badge :role="$user->role" /></dd>

                        <dt>Student profile</dt>
                        <dd>
                            @if ($user->student)
                                <a href="{{ route('students.show', $user->student) }}">
                                    <code class="av-hash">{{ $user->student->student_number }}</code>
                                </a>
                            @else
                                <span class="text-body-secondary">None</span>
                            @endif
                        </dd>

                        <dt>Joined</dt>
                        <dd>{{ $user->created_at?->format('d M Y, H:i') }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-4">
                    <x-stat-card label="Courses taught" :value="$user->courses_count"
                        icon="bi-journal-bookmark" variant="accent" />
                </div>
                <div class="col-6 col-xl-4">
                    <x-stat-card label="Records filed" :value="$user->created_records_count"
                        icon="bi-file-earmark-text" variant="navy" />
                </div>
                <div class="col-6 col-xl-4">
                    <x-stat-card label="Exports issued" :value="$user->exports_count"
                        icon="bi-file-earmark-pdf" variant="success" />
                </div>
            </div>

            <div class="card border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span>
                        <i class="bi bi-clipboard-data text-accent me-2" aria-hidden="true"></i>
                        Recent activity
                    </span>
                    <a href="{{ route('audit-logs.index', ['user_id' => $user->id]) }}"
                       class="btn btn-sm btn-outline-secondary">
                        View all
                    </a>
                </div>

                @if ($recentLogs->isEmpty())
                    <x-empty-state icon="bi-clipboard" title="No activity yet"
                        message="Nothing has been recorded for this account." />
                @else
                    <div class="table-responsive">
                        <table class="table av-table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th scope="col">Action</th>
                                    <th scope="col">Target</th>
                                    <th scope="col">When</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentLogs as $log)
                                    <tr>
                                        <td><x-action-badge :action="$log->action" /></td>
                                        <td class="text-body-secondary">
                                            <span class="av-hash">{{ $log->targetLabel() ?? '—' }}</span>
                                        </td>
                                        <td class="text-body-secondary text-nowrap">
                                            {{ $log->created_at?->format('d M Y, H:i') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @can('delete', $user)
        <div class="modal fade" id="deleteUserModal" tabindex="-1"
             aria-labelledby="deleteUserModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0">
                    <form method="POST" action="{{ route('users.destroy', $user) }}">
                        @csrf
                        @method('delete')

                        <div class="modal-header">
                            <h2 class="modal-title h5" id="deleteUserModalLabel">Delete this account?</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            <p>
                                <strong>{{ $user->name }}</strong> will lose access immediately.
                                This cannot be undone.
                            </p>

                            @if ($user->student)
                                <div class="alert alert-danger mb-0">
                                    <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>
                                    Their student profile
                                    <strong>{{ $user->student->student_number }}</strong>
                                    and its <strong>{{ $cascadedRecords }}</strong>
                                    academic {{ Str::plural('record', $cascadedRecords) }}
                                    will be deleted too.
                                </div>
                            @else
                                <p class="text-body-secondary small mb-0">
                                    Courses, records and exports they touched are kept — they are
                                    simply left without an owner. Audit log entries are never removed.
                                </p>
                            @endif
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-trash3 me-1" aria-hidden="true"></i>Delete account
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

</x-app-layout>
