<x-app-layout title="Add record">

    <x-page-header title="Add academic record" subtitle="File a grade for a student." icon="bi-file-earmark-plus" />

    <div class="row">
        <div class="col-xl-9">
            <div class="card border-0">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('records.store') }}" novalidate>
                        @csrf

                        @include('records.form', ['record' => null, 'students' => $students, 'courses' => $courses])

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('records.index') }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Create record
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</x-app-layout>
