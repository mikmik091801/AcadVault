<x-guest-layout title="Register">

    <h2 class="h3 fw-bold mb-1">Create your account</h2>
    <p class="text-body-secondary mb-4">
        New accounts start with student access. An administrator can change your role.
    </p>

    <form method="POST" action="{{ route('register') }}" novalidate>
        @csrf

        <div class="mb-3">
            <label for="last_name" class="form-label">Last name</label>
            <input id="last_name" type="text" name="last_name" value="{{ old('last_name') }}"
                   class="form-control @error('last_name') is-invalid @enderror"
                   placeholder="Dela Cruz"
                   required autofocus autocomplete="family-name">
            @error('last_name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="row g-3 mb-3">
            <div class="col-8">
                <label for="first_name" class="form-label">First name</label>
                <input id="first_name" type="text" name="first_name" value="{{ old('first_name') }}"
                       class="form-control @error('first_name') is-invalid @enderror"
                       placeholder="Juan"
                       required autocomplete="given-name">
                @error('first_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-4">
                <label for="middle_initial" class="form-label">M.I.</label>
                <input id="middle_initial" type="text" name="middle_initial"
                       value="{{ old('middle_initial') }}" maxlength="1"
                       class="form-control text-uppercase @error('middle_initial') is-invalid @enderror"
                       placeholder="P" autocomplete="additional-name">
                @error('middle_initial')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   class="form-control @error('email') is-invalid @enderror"
                   placeholder="you@university.edu"
                   required autocomplete="username">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="phone" class="form-label">
                Mobile number <span class="text-body-secondary fw-normal">(optional)</span>
            </label>
            <input id="phone" type="tel" name="phone" value="{{ old('phone') }}"
                   class="form-control @error('phone') is-invalid @enderror"
                   placeholder="09XX XXX XXXX" autocomplete="tel">
            @error('phone')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <div class="form-text">Used by the registrar to reach you about your records.</div>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input id="password" type="password" name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   placeholder="At least 8 characters"
                   required autocomplete="new-password">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <div class="form-text">Use a mix of letters, numbers and symbols.</div>
        </div>

        <div class="mb-4">
            <label for="password_confirmation" class="form-label">Confirm password</label>
            <input id="password_confirmation" type="password" name="password_confirmation"
                   class="form-control @error('password_confirmation') is-invalid @enderror"
                   placeholder="Re-enter your password"
                   required autocomplete="new-password">
            @error('password_confirmation')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex justify-content-between align-items-center gap-3">
            <a href="{{ route('login') }}" class="text-decoration-none text-body-secondary small">
                Already registered?
            </a>
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-person-plus me-1" aria-hidden="true"></i>Register
            </button>
        </div>
    </form>

</x-guest-layout>
