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

        // Note: EnsureFrontendRequestsAreStateful is intentionally NOT added here.
        // Existing API routes use the 'web' middleware (session + CSRF) for browser clients.
        // External API (/api/v1/*) uses auth:sanctum with Bearer tokens — no SPA session needed.
        // Adding EnsureFrontendRequestsAreStateful causes double session init → CSRF mismatch.

        // Trust proxies for ngrok
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Always return JSON for all /api/* routes, regardless of Accept header.
        // Without this, auth:sanctum redirects unauthenticated requests to /login (HTML 302).
        $exceptions->shouldRenderJsonWhen(fn ($request, $e) => $request->is('api/*'));
    })->create();
