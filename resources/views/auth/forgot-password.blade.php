<x-guest-layout title="Forgot password">

    <h2 class="h3 fw-bold mb-1">Forgot your password?</h2>
    <p class="text-body-secondary mb-4">
        Enter your email address and we'll send you a link to choose a new one.
    </p>

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-start gap-2" role="alert">
            <i class="bi bi-check-circle-fill mt-1" aria-hidden="true"></i>
            <div>{{ session('status') }}</div>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf

        <div class="mb-4">
            <label for="email" class="form-label">Email address</label>
            <div class="input-group has-validation">
                <span class="input-group-text bg-white text-body-secondary">
                    <i class="bi bi-envelope" aria-hidden="true"></i>
                </span>
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                       class="form-control @error('email') is-invalid @enderror"
                       placeholder="you@umindanao.edu.ph"
                       required autofocus>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center gap-3">
            <a href="{{ route('login') }}" class="text-decoration-none text-body-secondary small">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back to sign in
            </a>
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-send me-1" aria-hidden="true"></i>Send reset link
            </button>
        </div>
    </form>

</x-guest-layout>
