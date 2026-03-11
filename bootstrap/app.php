<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'manager' => \App\Http\Middleware\EnsureUserIsManager::class,
            'security' => \App\Http\Middleware\EnsureUserIsSecurity::class,
            'guest_monitor' => \App\Http\Middleware\EnsureUserIsGuest::class,
            'viewer' => \App\Http\Middleware\EnsureUserIsViewer::class,
        ]);

        // Enable Sanctum stateful authentication for API routes (supports both
        // session-based web clients and Bearer token external apps)
        $middleware->api(append: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        // Trust proxies for ngrok
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
