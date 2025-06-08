<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
        // Force HTTPS URLs in Codespace environment
        if (env('APP_ENV') === 'local' && str_contains(env('APP_URL', ''), '.app.github.dev')) {
            URL::forceScheme('https');
            
            // Ensure proper host detection in Codespace environment
            if (isset($_SERVER['HTTP_HOST']) && str_contains($_SERVER['HTTP_HOST'], '.app.github.dev')) {
                URL::forceRootUrl('https://' . $_SERVER['HTTP_HOST']);
            } else {
                URL::forceRootUrl(env('APP_URL'));
            }
        }
    }
}
