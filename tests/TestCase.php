<?php

namespace ElliottLawson\SocialiteLinear\Tests;

use ElliottLawson\SocialiteLinear\LinearServiceProvider;
use Laravel\Socialite\SocialiteServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            SocialiteServiceProvider::class,
            LinearServiceProvider::class,
        ];
    }
}
