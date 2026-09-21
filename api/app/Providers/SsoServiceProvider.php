<?php

namespace App\Providers;

use App\Socialite\JepflowSsoProvider;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Facades\Socialite;

class SsoServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Socialite::extend('jepflow_sso', function ($app) {
            $config = $app['config']['services.jepflow_sso'];

            return Socialite::buildProvider(JepflowSsoProvider::class, $config);
        });
    }
}
