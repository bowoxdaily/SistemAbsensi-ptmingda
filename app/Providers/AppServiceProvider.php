<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

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
        // Force HTTPS when using ngrok or in production
        if (config('app.env') === 'production' || request()->server('HTTP_X_FORWARDED_PROTO') === 'https') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Trust ngrok proxy
        if (request()->server('HTTP_X_FORWARDED_HOST')) {
            request()->server->set('HTTPS', 'on');
        }

        // Gunakan Bootstrap 5 untuk Paginasi
        Paginator::useBootstrapFive();
    }
}
