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
</div>
