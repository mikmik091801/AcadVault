<x-app-layout title="Add course">

    <x-page-header title="Add course" subtitle="Create a course and assign an instructor." icon="bi-journal-plus" />

    <div class="row">
        <div class="col-xl-9">
            <div class="card border-0">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('courses.store') }}" novalidate>
                        @csrf

                        @include('courses.form', ['course' => null, 'facultyMembers' => $facultyMembers])

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('courses.index') }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Create course
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</x-app-layout>
