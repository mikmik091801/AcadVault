<x-app-layout title="Edit record">

    <x-page-header
        title="Edit academic record"
        subtitle="{{ $record->student?->user?->name }} · {{ $record->course?->code }}"
        icon="bi-pencil-square" />

    <div class="row">
        <div class="col-xl-9">
            <div class="card border-0">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('records.update', $record) }}" novalidate>
                        @csrf
                        @method('put')

                        @include('records.form', ['record' => $record, 'students' => $students, 'courses' => $courses, 'rosters' => $rosters, 'prefill' => []])

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('records.show', $record) }}" class="btn btn-light">Cancel</a>
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
