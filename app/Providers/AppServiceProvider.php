<?php

declare(strict_types=1);

namespace App\Providers;

use App\Tenancy\TenantScope;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Tenant scope as singleton (one instance, but applied per-model)
        $this->app->singleton(TenantScope::class);
    }

    public function boot(): void
    {
        // Force HTTPS in production
        if (App::environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Ensure web requests never depend on a database sessions table.
        Config::set('session.driver', 'file');
        Config::set('session.files', storage_path('framework/sessions'));

        // Set available locales from config/env
        $locales = explode(',', (string) Config::get('app.available_locales', 'pt_BR,en,es'));
        Config::set('app.available_locales', array_map('trim', $locales));
    }
}
