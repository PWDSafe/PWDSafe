<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Sentry\Laravel\Integration;

return Application::configure()
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/health',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);

        // Token-based clients (browser extension, CLI) authenticate with a
        // Sanctum bearer token and carry no session cookie, so CSRF/origin
        // verification does not apply to these routes.
        $middleware->preventRequestForgery(except: [
            'api/auth/login',
            'api/auth/logout',
            'api/auth/devices/*',
            'api/groups',
            'api/groups/*/credentials',
            'api/credentials/*/move',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        Integration::handles($exceptions);
    })
    ->withSingletons([
        \Illuminate\Contracts\Console\Kernel::class => App\Console\Kernel::class,
    ])
    ->create();
