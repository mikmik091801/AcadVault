<div class="card border-0 h-100">
    <div class="card-header bg-white">
        <i class="bi bi-person-vcard text-accent me-2" aria-hidden="true"></i>
        Profile information
    </div>

    <div class="card-body">
        <p class="text-body-secondary small mb-4">
            Update your account's name and email address.
        </p>

        {{-- Resend verification lives outside the profile form to avoid nesting --}}
        @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
            <div class="alert alert-warning d-flex align-items-start gap-2" role="alert">
                <i class="bi bi-exclamation-triangle-fill mt-1" aria-hidden="true"></i>
                <div>
                    Your email address is unverified.
                    <form method="POST" action="{{ route('verification.send') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-link p-0 align-baseline text-decoration-underline">
                            Resend the verification email
                        </button>
                    </form>
                    @if (session('status') === 'verification-link-sent')
                        <div class="fw-semibold mt-2">A new verification link has been sent.</div>
                    @endif
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}" novalidate>
            @csrf
            @method('patch')

            <div class="mb-3">
                <label for="name" class="form-label">Full name</label>
                <input id="name" type="text" name="name"
                       value="{{ old('name', auth()->user()->name) }}"
                       class="form-control @error('name') is-invalid @enderror"
                       placeholder="Your full name"
                       required autocomplete="name">
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input id="email" type="email" name="email"
                       value="{{ old('email', auth()->user()->email) }}"
                       class="form-control @error('email') is-invalid @enderror"
                       placeholder="you@university.edu"
                       required autocomplete="username">
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label class="form-label">Role</label>
                <div>
                    <span class="badge badge-status badge-role">
                        <i class="bi {{ auth()->user()->role?->icon() ?? 'bi-person-badge' }}" aria-hidden="true"></i>
                        {{ auth()->user()->role?->label() ?? 'Not assigned' }}
                    </span>
                </div>
                <div class="form-text">Only an administrator can change your role.</div>
            </div>

            <div class="d-flex justify-content-end align-items-center gap-3">
                @if (session('status') === 'profile-updated')
                    <span class="text-success small">
                        <i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i>Saved
                    </span>
                @endif
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-save me-1" aria-hidden="true"></i>Save changes
                </button>
            </div>
        </form>
    </div>
</div>
