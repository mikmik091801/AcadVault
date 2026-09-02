<x-app-layout title="Add student">

    <x-page-header title="Add student" subtitle="Link a user account to a student profile." icon="bi-person-plus" />

    <div class="row">
        <div class="col-xl-9">
            <div class="card border-0">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('students.store') }}" novalidate>
                        @csrf

                        @include('students.form', ['student' => null, 'availableUsers' => $availableUsers])

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('students.index') }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Create student
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</x-app-layout>
