<x-guest-layout title="Reset password">

    <h2 class="h3 fw-bold mb-1">Choose a new password</h2>
    <p class="text-body-secondary mb-4">Your new password must be at least 8 characters.</p>

    <form method="POST" action="{{ route('password.store') }}" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input id="email" type="email" name="email"
                   value="{{ old('email', $request->email) }}"
                   class="form-control @error('email') is-invalid @enderror"
                   placeholder="you@university.edu"
                   required autofocus autocomplete="username">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">New password</label>
            <input id="password" type="password" name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   placeholder="At least 8 characters"
                   required autocomplete="new-password">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-4">
            <label for="password_confirmation" class="form-label">Confirm new password</label>
            <input id="password_confirmation" type="password" name="password_confirmation"
                   class="form-control @error('password_confirmation') is-invalid @enderror"
                   placeholder="Re-enter your new password"
                   required autocomplete="new-password">
            @error('password_confirmation')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-shield-lock me-1" aria-hidden="true"></i>Reset password
            </button>
        </div>
    </form>

</x-guest-layout>
