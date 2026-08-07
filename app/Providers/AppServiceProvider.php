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

        // Keep SMTP traffic below the provider's rate cap.
        // Default: 12 emails/minute (1 per 5 s) — safe for Mailgun, SendGrid flex,
        // and shared SMTP. Tune via MAIL_OUTBOUND_RATE_PER_MINUTE in .env.
        RateLimiter::for('lark-outbound-email', function (): Limit {
            $ratePerMinute = (int) config('mail.outbound_rate_per_minute', 12);
            return Limit::perMinute($ratePerMinute)->by('smtp_notifications');
        });
    }
}
