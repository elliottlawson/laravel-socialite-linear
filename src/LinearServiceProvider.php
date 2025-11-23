<?php

namespace ElliottLawson\SocialiteLinear;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Facades\Socialite;

class LinearServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Socialite::extend('linear', function (Application $app) {
            $config = $app->make('config')->get('services.linear');

            return Socialite::buildProvider(LinearProvider::class, $config);
        });
    }
}
