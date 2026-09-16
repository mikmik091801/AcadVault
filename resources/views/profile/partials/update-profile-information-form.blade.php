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
                <label for="last_name" class="form-label">Last name</label>
                <input id="last_name" type="text" name="last_name"
                       value="{{ old('last_name', auth()->user()->last_name) }}"
                       class="form-control @error('last_name') is-invalid @enderror"
                       placeholder="Dela Cruz"
                       required autocomplete="family-name">
                @error('last_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row g-3 mb-3">
                <div class="col-8">
                    <label for="first_name" class="form-label">First name</label>
                    <input id="first_name" type="text" name="first_name"
                           value="{{ old('first_name', auth()->user()->first_name) }}"
                           class="form-control @error('first_name') is-invalid @enderror"
                           placeholder="Juan"
                           required autocomplete="given-name">
                    @error('first_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-4">
                    <label for="middle_initial" class="form-label">M.I.</label>
                    <input id="middle_initial" type="text" name="middle_initial" maxlength="1"
                           value="{{ old('middle_initial', auth()->user()->middle_initial) }}"
                           class="form-control text-uppercase @error('middle_initial') is-invalid @enderror"
                           autocomplete="additional-name">
                    @error('middle_initial')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="phone" class="form-label">
                    Mobile number <span class="text-body-secondary fw-normal">(optional)</span>
                </label>
                <input id="phone" type="tel" name="phone"
                       value="{{ old('phone', auth()->user()->phone) }}"
                       class="form-control @error('phone') is-invalid @enderror"
                       placeholder="09XX XXX XXXX" autocomplete="tel">
                @error('phone')
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
