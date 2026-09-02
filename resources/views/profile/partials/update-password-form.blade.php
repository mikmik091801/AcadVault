<div class="card border-0 h-100">
    <div class="card-header bg-white">
        <i class="bi bi-shield-lock text-accent me-2" aria-hidden="true"></i>
        Update password
    </div>

    <div class="card-body">
        <p class="text-body-secondary small mb-4">
            Use a long, random password to keep your account secure.
        </p>

        <form method="POST" action="{{ route('password.update') }}" novalidate>
            @csrf
            @method('put')

            <div class="mb-3">
                <label for="update_password_current_password" class="form-label">Current password</label>
                <input id="update_password_current_password" type="password" name="current_password"
                       class="form-control @error('current_password', 'updatePassword') is-invalid @enderror"
                       placeholder="Your current password"
                       autocomplete="current-password">
                @error('current_password', 'updatePassword')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="update_password_password" class="form-label">New password</label>
                <input id="update_password_password" type="password" name="password"
                       class="form-control @error('password', 'updatePassword') is-invalid @enderror"
                       placeholder="At least 8 characters"
                       autocomplete="new-password">
                @error('password', 'updatePassword')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label for="update_password_password_confirmation" class="form-label">Confirm new password</label>
                <input id="update_password_password_confirmation" type="password" name="password_confirmation"
                       class="form-control @error('password_confirmation', 'updatePassword') is-invalid @enderror"
                       placeholder="Re-enter your new password"
                       autocomplete="new-password">
                @error('password_confirmation', 'updatePassword')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex justify-content-end align-items-center gap-3">
                @if (session('status') === 'password-updated')
                    <span class="text-success small">
                        <i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i>Saved
                    </span>
                @endif
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-key me-1" aria-hidden="true"></i>Update password
                </button>
            </div>
        </form>
    </div>
</div>
