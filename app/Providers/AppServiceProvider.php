<?php

namespace App\Providers;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Force HTTPS untuk production, staging, atau ketika FORCE_HTTPS=true di .env
        // Ini memastikan URL pagination tidak di-blokir sebagai mixed content
        if (! app()->environment('local') || env('FORCE_HTTPS', false)) {
            URL::forceScheme('https');
        }

        Inertia::share('flash', fn () => [
            'success' => Session::get('success'),
            'error' => Session::get('error'),
        ]);
    }
}
