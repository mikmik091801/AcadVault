<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Support\AuditLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        /*
         * Every blocked (403) access is written to audit_logs, whether it was
         * stopped by the role middleware (abort 403 -> HttpException) or by a
         * policy (AuthorizationException). Returning null lets Laravel render
         * the normal 403 page afterwards.
         */
        $exceptions->render(function (Throwable $e, Request $request) {
            $status = match (true) {
                $e instanceof AuthorizationException => 403,
                $e instanceof HttpExceptionInterface => $e->getStatusCode(),
                default => null,
            };

            if ($status === 403) {
                AuditLogger::denied($request->path(), $request->user());
            }

            return null;
        });
    })->create();
