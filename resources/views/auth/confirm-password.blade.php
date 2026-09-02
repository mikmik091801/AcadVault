<x-guest-layout title="Confirm password">

    <h2 class="h3 fw-bold mb-1">Confirm your password</h2>
    <p class="text-body-secondary mb-4">
        This is a secure area. Please confirm your password before continuing.
    </p>

    <form method="POST" action="{{ route('password.confirm') }}" novalidate>
        @csrf

        <div class="mb-4">
            <label for="password" class="form-label">Password</label>
            <div class="input-group has-validation">
                <span class="input-group-text bg-white text-body-secondary">
                    <i class="bi bi-lock" aria-hidden="true"></i>
                </span>
                <input id="password" type="password" name="password"
                       class="form-control @error('password') is-invalid @enderror"
                       placeholder="Enter your password"
                       required autocomplete="current-password">
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-check2-circle me-1" aria-hidden="true"></i>Confirm
            </button>
        </div>
    </form>

</x-guest-layout>
