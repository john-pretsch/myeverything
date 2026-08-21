<?php

namespace App\Providers;

use Illuminate\Auth\Middleware\Authenticate;
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
        // This app has no server-rendered login page to redirect guests to
        // (Next.js owns the UI) — always fall through to the standard
        // AuthenticationException, which renders as JSON 401 for api/* per
        // bootstrap/app.php's shouldRenderJsonWhen. Without this, an
        // unauthenticated request that doesn't send an explicit
        // "Accept: application/json" header hits Authenticate's default
        // redirect-to-named-route('login') behavior, which throws
        // RouteNotFoundException (no such route exists) and 500s instead
        // of cleanly 401ing.
        Authenticate::redirectUsing(fn () => null);
    }
}
