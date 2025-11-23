<?php

namespace ElliottLawson\SocialiteLinear\Tests;

use ElliottLawson\SocialiteLinear\LinearServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LinearServiceProvider::class,
        ];
    }
}
