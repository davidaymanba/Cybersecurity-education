<?php

namespace App\Providers;

use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Pagination\Paginator;
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
        // Only trust explicitly configured reverse proxies (see config/app.php).
        $proxies = config('app.trusted_proxies', []);
        if ($proxies !== []) {
            TrustProxies::at($proxies);
        }

        Paginator::defaultView('pagination.cyber');

        // The locale is resolved per request from the session in App\Http\Middleware\SetLocale.
    }
}
