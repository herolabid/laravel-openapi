<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi;

use Illuminate\Support\ServiceProvider;

/**
 * Laravel OpenAPI Service Provider.
 *
 * Bootstraps the package by registering bindings, routes, commands,
 * views, and configuration.
 */
class LaravelOpenApiServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/openapi.php',
            'openapi'
        );

        $this->registerBindings();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerRoutes();
        $this->registerViews();
        $this->registerCommands();
    }

    /**
     * Register service bindings.
     */
    protected function registerBindings(): void
    {
        // Register module path resolver
        $this->app->singleton(
            \HerolabID\LaravelOpenApi\Infrastructure\Scanner\ModulePathResolver::class,
            function ($app) {
                return new \HerolabID\LaravelOpenApi\Infrastructure\Scanner\ModulePathResolver(
                    config('openapi.modules', [])
                );
            }
        );

        // Auto-register module paths
        if (config('openapi.modules.enabled', true)) {
            $this->registerModulePaths();
        }

        // Contracts bindings will be added as we implement classes
        // This will use dependency injection and Laravel's container

        // Example (to be implemented):
        // $this->app->singleton(
        //     \HerolabID\LaravelOpenApi\Contracts\CacheInterface::class,
        //     \HerolabID\LaravelOpenApi\Infrastructure\Cache\CacheManager::class
        // );
    }

    /**
     * Register module paths to scan configuration.
     */
    protected function registerModulePaths(): void
    {
        /** @var \HerolabID\LaravelOpenApi\Infrastructure\Scanner\ModulePathResolver $resolver */
        $resolver = $this->app->make(
            \HerolabID\LaravelOpenApi\Infrastructure\Scanner\ModulePathResolver::class
        );

        $modulePaths = $resolver->resolve();

        // Merge module paths with existing scan configuration
        $scanConfig = config('openapi.scan', []);
        $scanConfig['controllers'] = array_merge(
            $scanConfig['controllers'] ?? [],
            $modulePaths['controllers']
        );
        $scanConfig['models'] = array_merge(
            $scanConfig['models'] ?? [],
            $modulePaths['models']
        );

        // Update config
        config(['openapi.scan' => $scanConfig]);
    }

    /**
     * Register publishable resources.
     */
    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish config
            $this->publishes([
                __DIR__ . '/../config/openapi.php' => config_path('openapi.php'),
            ], 'openapi-config');

            // Publish views
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/openapi'),
            ], 'openapi-views');
        }
    }

    /**
     * Register routes.
     */
    protected function registerRoutes(): void
    {
        if (! config('openapi.ui.swagger') && ! config('openapi.ui.redoc')) {
            return;
        }

        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }

    /**
     * Register views.
     */
    protected function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'openapi');
    }

    /**
     * Register Artisan commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                // Commands will be registered here
                // \HerolabID\LaravelOpenApi\Console\Commands\GenerateCommand::class,
                // \HerolabID\LaravelOpenApi\Console\Commands\ClearCacheCommand::class,
                // \HerolabID\LaravelOpenApi\Console\Commands\ValidateCommand::class,
            ]);
        }
    }
}
