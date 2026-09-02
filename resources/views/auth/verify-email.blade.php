<x-guest-layout title="Verify email">

    <h2 class="h3 fw-bold mb-1">Verify your email</h2>
    <p class="text-body-secondary mb-4">
        Thanks for signing up. Please confirm your email address by clicking the link we
        just emailed you. If you didn't get it, we'll gladly send another.
    </p>

    @if (session('status') === 'verification-link-sent')
        <div class="alert alert-success d-flex align-items-start gap-2" role="alert">
            <i class="bi bi-check-circle-fill mt-1" aria-hidden="true"></i>
            <div>A new verification link has been sent to your email address.</div>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center gap-3">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-link text-body-secondary text-decoration-none px-0">
                <i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i>Log out
            </button>
        </form>

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-envelope-arrow-up me-1" aria-hidden="true"></i>Resend email
            </button>
        </form>
    </div>

</x-guest-layout>
