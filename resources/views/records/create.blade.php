<x-app-layout title="Add record">

    <x-page-header title="Add academic record" subtitle="File a grade for a student enrolled in one of your classes." icon="bi-file-earmark-plus" />

    @php
        $cancelUrl = $returnToCourse && $prefill['course_id']
            ? route('courses.show', $prefill['course_id'])
            : route('records.index');
    @endphp

    <div class="row">
        <div class="col-xl-9">
            <div class="card border-0">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('records.store') }}" novalidate>
                        @csrf

                        @if ($returnToCourse)
                            <input type="hidden" name="return_to_course" value="1">
                        @endif

                        @include('records.form', ['record' => null, 'students' => $students, 'courses' => $courses, 'rosters' => $rosters, 'prefill' => $prefill])

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ $cancelUrl }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Save grade
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</x-app-layout>
