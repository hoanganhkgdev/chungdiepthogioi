<?php

namespace App\Providers;

use App\Auth\LegacyEloquentUserProvider;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
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
        Event::listen(Login::class, fn (Login $e) => $e->user->forceFill(['last_login_at' => now()])->saveQuietly());

        Auth::provider('eloquent-legacy', fn ($app, array $config) => new LegacyEloquentUserProvider($app['hash'], $config['model']));
    }
}
