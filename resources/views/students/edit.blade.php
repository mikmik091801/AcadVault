<x-app-layout title="Edit student">

    <x-page-header
        title="Edit student"
        subtitle="{{ $student->user?->name }} · {{ $student->student_number }}"
        icon="bi-pencil-square" />

    <div class="row">
        <div class="col-xl-9">
            <div class="card border-0">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('students.update', $student) }}" novalidate>
                        @csrf
                        @method('put')

                        @include('students.form', ['student' => $student, 'availableUsers' => $availableUsers])

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('students.show', $student) }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-save me-1" aria-hidden="true"></i>Save changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</x-app-layout>
