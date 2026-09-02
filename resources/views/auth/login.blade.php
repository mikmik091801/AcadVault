<x-guest-layout title="Sign in">

    <h2 class="h3 fw-bold mb-1">Welcome back</h2>
    <p class="text-body-secondary mb-4">Sign in to access your academic records.</p>

    {{-- Session status (e.g. "password reset link sent") --}}
    @if (session('status'))
        <div class="alert alert-success d-flex align-items-start gap-2" role="alert">
            <i class="bi bi-check-circle-fill mt-1" aria-hidden="true"></i>
            <div>{{ session('status') }}</div>
        </div>
    @endif

    {{-- Errors that are not tied to a single field --}}
    @if ($errors->any() && ! $errors->has('email') && ! $errors->has('password'))
        <div class="alert alert-danger d-flex align-items-start gap-2" role="alert">
            <i class="bi bi-exclamation-triangle-fill mt-1" aria-hidden="true"></i>
            <div>{{ $errors->first() }}</div>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <div class="input-group has-validation">
                <span class="input-group-text bg-white text-body-secondary">
                    <i class="bi bi-envelope" aria-hidden="true"></i>
                </span>
                <input id="email" type="email" name="email"
                       value="{{ old('email') }}"
                       class="form-control @error('email') is-invalid @enderror"
                       placeholder="you@university.edu"
                       required autofocus autocomplete="username">
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <label for="password" class="form-label">Password</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="small text-decoration-none text-accent fw-semibold">
                        Forgot password?
                    </a>
                @endif
            </div>
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

        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" id="remember_me" name="remember">
            <label class="form-check-label text-body-secondary" for="remember_me">
                Keep me signed in on this device
            </label>
        </div>

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary w-100 py-2">
                <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>
                Sign in
            </button>
        </div>
    </form>

    @if (Route::has('register'))
        <p class="text-center text-body-secondary small mt-4 mb-0">
            Don't have an account?
            <a href="{{ route('register') }}" class="text-decoration-none text-accent fw-semibold">Register</a>
        </p>
    @endif

    <p class="text-center text-body-secondary mt-4 mb-0" style="font-size:.78rem;">
        <i class="bi bi-shield-check me-1" aria-hidden="true"></i>
        All sign-in attempts are recorded in the audit log.
    </p>

</x-guest-layout>
