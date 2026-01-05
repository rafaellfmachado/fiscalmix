<?php

namespace App\Providers;

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
        // Register tenant middleware alias
        $this->app['router']->aliasMiddleware('tenant', \App\Http\Middleware\SetTenantContext::class);
    }
}
