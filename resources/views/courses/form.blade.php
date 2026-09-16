{{-- Shared create/edit fields for a course --}}
@props([
    'course' => null,
    'facultyMembers' => collect(),
])

<div class="row g-3">
    <div class="col-md-4">
        <label for="code" class="form-label">Course code</label>
        <input id="code" type="text" name="code"
               value="{{ old('code', $course?->code) }}"
               class="form-control @error('code') is-invalid @enderror"
               placeholder="e.g. CS101" required>
        @error('code')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-8">
        <label for="title" class="form-label">Course title</label>
        <input id="title" type="text" name="title"
               value="{{ old('title', $course?->title) }}"
               class="form-control @error('title') is-invalid @enderror"
               placeholder="e.g. Introduction to Computing" required>
        @error('title')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-8">
        <label for="faculty_id" class="form-label">Instructor</label>
        <select id="faculty_id" name="faculty_id"
                class="form-select @error('faculty_id') is-invalid @enderror">
            <option value="">Unassigned</option>
            @foreach ($facultyMembers as $member)
                <option value="{{ $member->id }}" @selected(old('faculty_id', $course?->faculty_id) == $member->id)>
                    {{ $member->name }} — {{ $member->email }}
                </option>
            @endforeach
        </select>
        @error('faculty_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text">Only users with the Faculty role can be assigned.</div>
    </div>

    <div class="col-md-4">
        <label for="units" class="form-label">Units</label>
        <input id="units" type="number" name="units" min="1" max="12"
               value="{{ old('units', $course?->units ?? 3) }}"
               class="form-control @error('units') is-invalid @enderror" required>
        @error('units')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <hr class="my-2">
        <h2 class="h6 mb-1">Curriculum placement</h2>
        <p class="text-body-secondary small">
            Where this subject sits in a program. Students are offered the subjects
            for their own program and year level. Leave the program blank to make
            the subject available to every student.
        </p>
    </div>

    <div class="col-md-6">
        @php $selectedProgram = old('program', $course?->program); @endphp

        <label for="program" class="form-label">Program</label>
        <select id="program" name="program"
                class="form-select @error('program') is-invalid @enderror">
            <option value="" @selected(! $selectedProgram)>Open to all programs</option>
            @foreach (\App\Enums\Program::groupedByCollege() as $college => $programs)
                <optgroup label="{{ $college }}">
                    @foreach ($programs as $program)
                        <option value="{{ $program->value }}" @selected($selectedProgram === $program->value)>
                            {{ $program->value }}
                        </option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        @error('program')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label for="year_level" class="form-label">Year level</label>
        <select id="year_level" name="year_level"
                class="form-select @error('year_level') is-invalid @enderror">
            <option value="">Any year</option>
            @foreach ([1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year', 5 => '5th Year'] as $value => $label)
                <option value="{{ $value }}" @selected((string) old('year_level', $course?->year_level) === (string) $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('year_level')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label for="semester" class="form-label">Semester</label>
        <select id="semester" name="semester"
                class="form-select @error('semester') is-invalid @enderror">
            <option value="">Any semester</option>
            @foreach ([1 => '1st Semester', 2 => '2nd Semester', 3 => 'Summer'] as $value => $label)
                <option value="{{ $value }}" @selected((string) old('semester', $course?->semester) === (string) $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('semester')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
