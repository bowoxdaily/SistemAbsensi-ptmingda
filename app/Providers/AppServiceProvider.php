<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;

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

        // Keep SMTP traffic below Lark's frequency caps (60 emails/minute).
        RateLimiter::for('lark-outbound-email', function (): Limit {
            return Limit::perMinute(50)->by('smtp_notifications');
        });
    }
}
