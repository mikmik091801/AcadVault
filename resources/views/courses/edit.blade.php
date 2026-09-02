<x-app-layout title="Edit course">

    <x-page-header
        title="Edit course"
        subtitle="{{ $course->code }} · {{ $course->title }}"
        icon="bi-pencil-square" />

    <div class="row">
        <div class="col-xl-9">
            <div class="card border-0">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('courses.update', $course) }}" novalidate>
                        @csrf
                        @method('put')

                        @include('courses.form', ['course' => $course, 'facultyMembers' => $facultyMembers])

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('courses.show', $course) }}" class="btn btn-light">Cancel</a>
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
