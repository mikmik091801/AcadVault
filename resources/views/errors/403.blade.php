<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Access denied · AcadVault</title>
    @vite(['resources/css/app.scss', 'resources/js/app.js'])
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card border-0 text-center">
                    <div class="card-body p-5">
                        <div class="av-verify-hero av-verify-tampered mx-auto">
                            <i class="bi bi-shield-exclamation" aria-hidden="true"></i>
                        </div>

                        <h1 class="h3 fw-bold mb-2">Access denied</h1>

                        <p class="text-body-secondary mb-4">
                            Your role does not permit access to this page.
                            This attempt has been recorded in the audit log.
                        </p>

                        <div class="d-flex justify-content-center gap-2">
                            @auth
                                <a href="{{ route('dashboard') }}" class="btn btn-primary">
                                    <i class="bi bi-speedometer2 me-1" aria-hidden="true"></i>Back to dashboard
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="btn btn-primary">
                                    <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>Sign in
                                </a>
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
