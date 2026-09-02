<?php

namespace App\Providers;

use App\Listeners\LogAuthenticationEvent;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Paginator links render with Bootstrap 5 markup to match the design.
        Paginator::useBootstrapFive();

        // Authentication activity is audited from Laravel's own auth events,
        // so every sign-in path is covered, not just the login form.
        Event::listen(Login::class, [LogAuthenticationEvent::class, 'onLogin']);
        Event::listen(Logout::class, [LogAuthenticationEvent::class, 'onLogout']);
        Event::listen(Failed::class, [LogAuthenticationEvent::class, 'onFailed']);
        Event::listen(Lockout::class, [LogAuthenticationEvent::class, 'onLockout']);
    }
}
