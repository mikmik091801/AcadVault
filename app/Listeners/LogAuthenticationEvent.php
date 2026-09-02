<?php

namespace App\Listeners;

use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

/**
 * Writes authentication activity to the audit trail.
 *
 * Hooked to Laravel's own auth events rather than the controller, so any sign
 * in path — the login form, remember-me, or a programmatic Auth::login — is
 * recorded the same way.
 *
 * NOTE: these methods are deliberately named on*, not handle*. Laravel's event
 * discovery auto-registers any public `handle*` method whose first parameter is
 * a typed event; combined with the explicit Event::listen calls in
 * AppServiceProvider that would register each listener twice and write every
 * entry to the audit log twice over.
 */
class LogAuthenticationEvent
{
    public function onLogin(Login $event): void
    {
        $user = $event->user instanceof User ? $event->user : null;

        AuditLogger::log(AuditLogger::LOGIN_SUCCESS, null, $user);
    }

    public function onLogout(Logout $event): void
    {
        $user = $event->user instanceof User ? $event->user : null;

        AuditLogger::log(AuditLogger::LOGOUT, null, $user);
    }

    /**
     * A failed attempt records the email that was tried, never the password.
     */
    public function onFailed(Failed $event): void
    {
        $email = $event->credentials['email'] ?? null;
        $user = $event->user instanceof User ? $event->user : null;

        AuditLogger::log(
            AuditLogger::LOGIN_FAILED,
            $email ? 'email: '.$email : null,
            $user,
        );
    }

    /**
     * Repeated failures tripped the rate limiter — a brute force signal.
     */
    public function onLockout(Lockout $event): void
    {
        $email = $event->request->input('email');

        AuditLogger::log(
            AuditLogger::LOGIN_LOCKOUT,
            $email ? 'email: '.$email : null,
        );
    }
}
