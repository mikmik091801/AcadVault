{{-- Shared create/edit fields for a user account --}}
@props([
    'user' => null,
])

@php
    // Nobody changes their own role, so the field locks itself when an admin
    // edits their own account (UserPolicy::updateRole).
    $canSetRole = $user === null || auth()->user()->can('updateRole', $user);
    $currentRole = old('role', $user?->role?->value ?? \App\Enums\Role::Student->value);

    // old() can hold anything a form submitted, so never assume it is valid.
    $roleCase = \App\Enums\Role::tryFrom((string) $currentRole) ?? \App\Enums\Role::Student;
    $hasStudentProfile = (bool) $user?->student;
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label for="last_name" class="form-label">Last name</label>
        <input id="last_name" type="text" name="last_name"
               value="{{ old('last_name', $user?->last_name) }}"
               class="form-control @error('last_name') is-invalid @enderror"
               autocomplete="family-name" required>
        @error('last_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="first_name" class="form-label">First name</label>
        <input id="first_name" type="text" name="first_name"
               value="{{ old('first_name', $user?->first_name) }}"
               class="form-control @error('first_name') is-invalid @enderror"
               autocomplete="given-name" required>
        @error('first_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-2">
        <label for="middle_initial" class="form-label">M.I.</label>
        <input id="middle_initial" type="text" name="middle_initial" maxlength="1"
               value="{{ old('middle_initial', $user?->middle_initial) }}"
               class="form-control text-uppercase @error('middle_initial') is-invalid @enderror"
               autocomplete="additional-name">
        @error('middle_initial')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="phone" class="form-label">
            Mobile number <span class="text-body-secondary fw-normal">(optional)</span>
        </label>
        <input id="phone" type="tel" name="phone"
               value="{{ old('phone', $user?->phone) }}"
               class="form-control @error('phone') is-invalid @enderror"
               placeholder="09XX XXX XXXX" autocomplete="tel">
        @error('phone')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="email" class="form-label">Email address</label>
        <input id="email" type="email" name="email"
               value="{{ old('email', $user?->email) }}"
               class="form-control @error('email') is-invalid @enderror"
               autocomplete="email" required>
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="role" class="form-label">Role</label>

        <select id="role" name="role"
                class="form-select @error('role') is-invalid @enderror"
                @disabled(! $canSetRole) required>
            @foreach (\App\Enums\Role::cases() as $case)
                <option value="{{ $case->value }}" @selected($roleCase === $case)>
                    {{ $case->label() }}
                </option>
            @endforeach
        </select>

        @unless ($canSetRole)
            {{-- A disabled select submits nothing; keep the current value. --}}
            <input type="hidden" name="role" value="{{ $user->role->value }}">
        @endunless

        @error('role')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror

        <div class="form-text">
            @if (! $canSetRole)
                You cannot change your own role. Ask another administrator to do it.
            @elseif ($hasStudentProfile)
                This account has a student profile, so it must keep the student role.
            @else
                Role changes are written to the audit log.
            @endif
        </div>
    </div>

    <div class="col-md-6 d-flex align-items-end">
        <div class="alert alert-light border w-100 mb-0 py-2 small">
            <i class="bi bi-shield-lock text-accent me-1" aria-hidden="true"></i>
            <strong>{{ $roleCase->label() }}</strong> —
            @switch($roleCase->value)
                @case(\App\Enums\Role::Admin->value)
                    full access, including users and audit logs.
                    @break
                @case(\App\Enums\Role::Registrar->value)
                    manages students, courses, records and issues exports.
                    @break
                @case(\App\Enums\Role::Faculty->value)
                    sees and files records for their own courses only.
                    @break
                @default
                    sees only their own records and exports.
            @endswitch
        </div>
    </div>

    <div class="col-12">
        <hr class="my-2">
        <h2 class="h6 mb-1">{{ $user ? 'Change password' : 'Password' }}</h2>
        @if ($user)
            <p class="text-body-secondary small">
                Leave both fields blank to keep the current password.
            </p>
        @endif
    </div>

    <div class="col-md-6">
        <label for="password" class="form-label">
            {{ $user ? 'New password' : 'Password' }}
        </label>
        <input id="password" type="password" name="password"
               class="form-control @error('password') is-invalid @enderror"
               autocomplete="new-password" @required(! $user)>
        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="password_confirmation" class="form-label">Confirm password</label>
        <input id="password_confirmation" type="password" name="password_confirmation"
               class="form-control" autocomplete="new-password" @required(! $user)>
    </div>
</div>
