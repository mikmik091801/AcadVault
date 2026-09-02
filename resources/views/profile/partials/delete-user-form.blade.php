<div class="card border-0">
    <div class="card-header bg-white">
        <i class="bi bi-exclamation-octagon text-danger me-2" aria-hidden="true"></i>
        Delete account
    </div>

    <div class="card-body">
        <p class="text-body-secondary small mb-3">
            Once your account is deleted, all of its resources and data are permanently
            removed. Download anything you need to keep before continuing. Audit log
            entries are retained for institutional record-keeping.
        </p>

        <button type="button" class="btn btn-outline-danger"
                data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
            <i class="bi bi-trash3 me-1" aria-hidden="true"></i>Delete account
        </button>
    </div>
</div>

<div class="modal fade" id="deleteAccountModal" tabindex="-1"
     aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <form method="POST" action="{{ route('profile.destroy') }}">
                @csrf
                @method('delete')

                <div class="modal-header">
                    <h2 class="modal-title h5" id="deleteAccountModalLabel">
                        Delete your account?
                    </h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <p class="text-body-secondary small">
                        This cannot be undone. Enter your password to confirm.
                    </p>

                    <label for="delete_password" class="form-label">Password</label>
                    <input id="delete_password" type="password" name="password"
                           class="form-control @error('password', 'userDeletion') is-invalid @enderror"
                           placeholder="Your password">
                    @error('password', 'userDeletion')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash3 me-1" aria-hidden="true"></i>Delete account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Re-open the modal if validation failed inside it --}}
@if ($errors->userDeletion->isNotEmpty())
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            new bootstrap.Modal(document.getElementById('deleteAccountModal')).show();
        });
    </script>
@endif
