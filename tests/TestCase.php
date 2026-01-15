<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use HerolabID\LaravelOpenApi\LaravelOpenApiServiceProvider;

/**
 * Base test case for all tests.
 */
abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Additional setup can be added here
    }

    /**
     * Get package providers.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return array<int, class-string<\Illuminate\Support\ServiceProvider>>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LaravelOpenApiServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        // Setup default test configuration
        $app['config']->set('openapi.cache.enabled', false);
        $app['config']->set('openapi.hot_reload', false);
    }
}
