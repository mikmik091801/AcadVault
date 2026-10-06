<x-app-layout title="Trash">

    <x-page-header
        title="Trash"
        subtitle="Soft-deleted items. Restore one to bring it back with its records."
        icon="bi-trash3" />

    <div class="card border-0 mb-4">
        <div class="card-header bg-white"><strong>Students</strong></div>
        <div class="card-body">
            @if ($students->isEmpty())
                <p class="text-body-secondary mb-0">No deleted students.</p>
            @else
                <table class="table align-middle">
                    <thead><tr><th>Student</th><th>Number</th><th>Deleted</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($students as $student)
                            <tr>
                                <td>{{ $student->user?->name ?? '—' }}</td>
                                <td><code>{{ $student->student_number }}</code></td>
                                <td>{{ $student->deleted_at?->format('M d, Y h:i A') }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('trashed.students.restore', $student->id) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success"><i class="bi bi-arrow-counterclockwise me-1"></i>Restore</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <div class="card border-0 mb-4">
        <div class="card-header bg-white"><strong>Courses</strong></div>
        <div class="card-body">
            @if ($courses->isEmpty())
                <p class="text-body-secondary mb-0">No deleted courses.</p>
            @else
                <table class="table align-middle">
                    <thead><tr><th>Course</th><th>Faculty</th><th>Deleted</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($courses as $course)
                            <tr>
                                <td><code>{{ $course->code }}</code> — {{ $course->title }}</td>
                                <td>{{ $course->faculty?->name ?? '—' }}</td>
                                <td>{{ $course->deleted_at?->format('M d, Y h:i A') }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('trashed.courses.restore', $course->id) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success"><i class="bi bi-arrow-counterclockwise me-1"></i>Restore</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <div class="card border-0">
        <div class="card-header bg-white"><strong>User accounts</strong></div>
        <div class="card-body">
            @if ($users->isEmpty())
                <p class="text-body-secondary mb-0">No deleted accounts.</p>
            @else
                <table class="table align-middle">
                    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Deleted</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->role?->value ?? '—' }}</td>
                                <td>{{ $user->deleted_at?->format('M d, Y h:i A') }}</td>
                                <td class="text-end">
                                    @can('delete', $user)
                                        <form method="POST" action="{{ route('trashed.users.restore', $user->id) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-success"><i class="bi bi-arrow-counterclockwise me-1"></i>Restore</button>
                                        </form>
                                    @else
                                        <span class="text-body-secondary small">Admin only</span>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

</x-app-layout>
