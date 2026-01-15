<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | OpenAPI Information
    |--------------------------------------------------------------------------
    |
    | Basic information about your API. These values will be used in the
    | generated OpenAPI specification info section.
    |
    */
    'info' => [
        'title' => env('APP_NAME', 'API Documentation'),
        'version' => '1.0.0',
        'description' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Scan Paths
    |--------------------------------------------------------------------------
    |
    | Paths to scan for OpenAPI attributes. By default, we scan all
    | controllers and models following Laravel conventions.
    |
    */
    'scan' => [
        'controllers' => [
            app_path('Http/Controllers'),
        ],
        'models' => [
            app_path('Models'),
        ],
        'exclude' => [
            // Paths to exclude from scanning
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Module Support
    |--------------------------------------------------------------------------
    |
    | Enable automatic detection and scanning of modular structures.
    | Supports nwdart/laravel-modules and similar module packages.
    |
    */
    'modules' => [
        'enabled' => true,

        // Auto-detect nwdart/laravel-modules package
        'auto_detect' => true,

        // Custom module paths (if not using auto-detection)
        'paths' => [
            // base_path('modules'),
            // base_path('app/Modules'),
        ],

        // Subdirectories to scan within each module
        'scan_paths' => [
            'controllers' => ['Http/Controllers', 'Controllers'],
            'models' => ['Models', 'Entities'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Enable caching for faster performance. Cache is automatically
    | invalidated when source files change.
    |
    */
    'cache' => [
        'enabled' => !app()->environment('local'),
        'driver' => env('CACHE_DRIVER', 'file'),
        'ttl' => 3600, // 1 hour in seconds
        'key' => 'openapi:spec',
    ],

    /*
    |--------------------------------------------------------------------------
    | Hot Reload
    |--------------------------------------------------------------------------
    |
    | Enable hot reload in development to automatically regenerate
    | the spec when files change.
    |
    */
    'hot_reload' => env('OPENAPI_HOT_RELOAD', app()->environment('local')),

    /*
    |--------------------------------------------------------------------------
    | UI Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which UIs to enable and their settings.
    |
    */
    'ui' => [
        'swagger' => true,
        'redoc' => true,
        'default' => 'swagger', // Default UI to redirect to
        'route_prefix' => 'api/docs',
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Schemes
    |--------------------------------------------------------------------------
    |
    | Security schemes for your API. Auto-detection is enabled by default
    | to detect Laravel Sanctum/Passport.
    |
    */
    'security' => [
        'auto_detect' => true,
        'schemes' => [
            // Manual security schemes if needed
            // 'bearer' => [
            //     'type' => 'http',
            //     'scheme' => 'bearer',
            //     'bearerFormat' => 'JWT',
            // ],
        ],
    ],

];
