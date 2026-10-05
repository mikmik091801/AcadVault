{{-- Shared create/edit fields for an academic record --}}
@props([
    'record' => null,
    'students' => collect(),
    'courses' => collect(),
    'rosters' => [],
    'prefill' => [],
])

@php
    $selectedCourse = old('course_id', $record?->course_id ?? ($prefill['course_id'] ?? null));
    $selectedStudent = old('student_id', $record?->student_id ?? ($prefill['student_id'] ?? null));
@endphp

{{-- The course is chosen first so the student list can be narrowed to the
     people actually enrolled in it (app.js reads data-av-rosters). --}}
<div class="row g-3" data-av-rosters='@json($rosters)'>
    <div class="col-md-6">
        <label for="course_id" class="form-label">Course</label>
        <select id="course_id" name="course_id"
                class="form-select @error('course_id') is-invalid @enderror" required>
            <option value="">Select a course…</option>
            @foreach ($courses as $option)
                <option value="{{ $option->id }}" @selected($selectedCourse == $option->id)>
                    {{ $option->code }} — {{ $option->title }}
                </option>
            @endforeach
        </select>
        @error('course_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        @if (auth()->user()->isFaculty())
            <div class="form-text">Only the courses you teach are listed.</div>
        @endif
    </div>

    <div class="col-md-6">
        <label for="student_id" class="form-label">Student</label>
        <select id="student_id" name="student_id"
                class="form-select @error('student_id') is-invalid @enderror" required
                @if ($record) data-av-keep="{{ $record->student_id }}" @endif>
            <option value="">Select a student…</option>
            @foreach ($students as $option)
                <option value="{{ $option->id }}" @selected($selectedStudent == $option->id)>
                    {{ $option->user?->name }} — {{ $option->student_number }}
                </option>
            @endforeach
        </select>
        @error('student_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text" data-av-roster-hint>Only students enrolled in the course can be graded.</div>
    </div>

    <div class="col-md-4">
        <label for="grade" class="form-label">Grade</label>
        <input id="grade" type="text" name="grade" list="gradeSuggestions"
               value="{{ old('grade', $record?->grade) }}"
               class="form-control @error('grade') is-invalid @enderror"
               placeholder="e.g. 1.75" autocomplete="off" required>
        <datalist id="gradeSuggestions">
            @foreach (['1.00', '1.25', '1.50', '1.75', '2.00', '2.25', '2.50', '2.75', '3.00', '5.00', 'INC'] as $suggestion)
                <option value="{{ $suggestion }}"></option>
            @endforeach
        </datalist>
        @error('grade')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text">
            <i class="bi bi-shield-lock me-1" aria-hidden="true"></i>Encrypted at rest.
        </div>
    </div>

    <div class="col-md-8">
        <label for="remarks" class="form-label">Remarks <span class="text-body-secondary fw-normal">(optional)</span></label>
        <input id="remarks" type="text" name="remarks" list="remarkSuggestions"
               value="{{ old('remarks', $record?->remarks) }}"
               class="form-control @error('remarks') is-invalid @enderror"
               placeholder="e.g. Passed" autocomplete="off">
        <datalist id="remarkSuggestions">
            @foreach (['Passed', 'Passed with distinction', 'Failed', 'Incomplete'] as $suggestion)
                <option value="{{ $suggestion }}"></option>
            @endforeach
        </datalist>
        @error('remarks')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
