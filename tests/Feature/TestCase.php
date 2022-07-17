<?php

namespace Tests\Feature;

use A2Workspace\SocialEntry\SocialEntry;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        SocialEntry::routes();
    }

    protected function getPackageProviders($app)
    {
        return [
            \Laravel\Socialite\SocialiteServiceProvider::class,
            \A2Workspace\SocialEntry\ServiceProvider::class,
            // ...
        ];
    }
}
