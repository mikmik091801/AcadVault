{{-- Shared create/edit fields for a student profile --}}
@props([
    'student' => null,
    'availableUsers' => collect(),
])

<div class="row g-3">
    <div class="col-md-6">
        <label for="user_id" class="form-label">User account</label>
        <select id="user_id" name="user_id"
                class="form-select @error('user_id') is-invalid @enderror" required>
            <option value="">Select a user account…</option>
            @foreach ($availableUsers as $option)
                <option value="{{ $option->id }}"
                    @selected(old('user_id', $student?->user_id) == $option->id)>
                    {{ $option->name }} — {{ $option->email }}
                </option>
            @endforeach
        </select>
        @error('user_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text">Only accounts without an existing student profile are listed.</div>
    </div>

    <div class="col-md-6">
        <label for="student_number" class="form-label">Student number</label>
        <input id="student_number" type="text" name="student_number"
               value="{{ old('student_number', $student?->student_number) }}"
               class="form-control @error('student_number') is-invalid @enderror"
               placeholder="e.g. 2026-00123" required>
        @error('student_number')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-8">
        <label for="program" class="form-label">Program</label>
        <input id="program" type="text" name="program"
               value="{{ old('program', $student?->program) }}"
               class="form-control @error('program') is-invalid @enderror"
               placeholder="e.g. BS Computer Science" required>
        @error('program')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="year_level" class="form-label">Year level</label>
        <select id="year_level" name="year_level"
                class="form-select @error('year_level') is-invalid @enderror" required>
            @foreach (range(1, 6) as $level)
                <option value="{{ $level }}" @selected(old('year_level', $student?->year_level ?? 1) == $level)>
                    {{ $level }}
                </option>
            @endforeach
        </select>
        @error('year_level')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
