<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Infrastructure\Scanner;

/**
 * Resolve module paths for OpenAPI scanning.
 *
 * Supports nwdart/laravel-modules and custom modular structures.
 */
class ModulePathResolver
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private array $config = []
    ) {
    }

    /**
     * Resolve all module paths for controllers and models.
     *
     * @return array{controllers: array<string>, models: array<string>}
     */
    public function resolve(): array
    {
        if (! $this->isEnabled()) {
            return ['controllers' => [], 'models' => []];
        }

        $paths = [
            'controllers' => [],
            'models' => [],
        ];

        // Auto-detect nwdart/laravel-modules
        if ($this->shouldAutoDetect() && $this->hasNwidartModules()) {
            $paths = $this->resolveNwidartModules();
        }

        // Add custom module paths
        $customPaths = $this->resolveCustomPaths();
        $paths['controllers'] = array_merge($paths['controllers'], $customPaths['controllers']);
        $paths['models'] = array_merge($paths['models'], $customPaths['models']);

        return $paths;
    }

    /**
     * Check if module scanning is enabled.
     */
    private function isEnabled(): bool
    {
        return (bool) ($this->config['enabled'] ?? false);
    }

    /**
     * Check if auto-detection is enabled.
     */
    private function shouldAutoDetect(): bool
    {
        return (bool) ($this->config['auto_detect'] ?? true);
    }

    /**
     * Check if nwdart/laravel-modules is installed.
     */
    private function hasNwidartModules(): bool
    {
        return class_exists(\Nwidart\Modules\Facades\Module::class);
    }

    /**
     * Resolve paths from nwdart/laravel-modules.
     *
     * @return array{controllers: array<string>, models: array<string>}
     */
    private function resolveNwidartModules(): array
    {
        $paths = [
            'controllers' => [],
            'models' => [],
        ];

        try {
            /** @var \Nwidart\Modules\Collection $modules */
            $modules = \Nwidart\Modules\Facades\Module::all();

            foreach ($modules as $module) {
                $modulePath = $module->getPath();

                // Add controller paths
                foreach ($this->getControllerSubPaths() as $subPath) {
                    $fullPath = $modulePath . '/' . $subPath;
                    if (is_dir($fullPath)) {
                        $paths['controllers'][] = $fullPath;
                    }
                }

                // Add model paths
                foreach ($this->getModelSubPaths() as $subPath) {
                    $fullPath = $modulePath . '/' . $subPath;
                    if (is_dir($fullPath)) {
                        $paths['models'][] = $fullPath;
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently fail if module system is not available
            // This allows the package to work even if modules are misconfigured
        }

        return $paths;
    }

    /**
     * Resolve custom module paths from config.
     *
     * @return array{controllers: array<string>, models: array<string>}
     */
    private function resolveCustomPaths(): array
    {
        $paths = [
            'controllers' => [],
            'models' => [],
        ];

        $customPaths = $this->config['paths'] ?? [];

        foreach ($customPaths as $modulePath) {
            if (! is_dir($modulePath)) {
                continue;
            }

            // Scan for modules in this path
            $moduleDirectories = $this->getModuleDirectories($modulePath);

            foreach ($moduleDirectories as $moduleDir) {
                // Add controller paths
                foreach ($this->getControllerSubPaths() as $subPath) {
                    $fullPath = $moduleDir . '/' . $subPath;
                    if (is_dir($fullPath)) {
                        $paths['controllers'][] = $fullPath;
                    }
                }

                // Add model paths
                foreach ($this->getModelSubPaths() as $subPath) {
                    $fullPath = $moduleDir . '/' . $subPath;
                    if (is_dir($fullPath)) {
                        $paths['models'][] = $fullPath;
                    }
                }
            }
        }

        return $paths;
    }

    /**
     * Get module directories from a base path.
     *
     * @return array<string>
     */
    private function getModuleDirectories(string $basePath): array
    {
        if (! is_dir($basePath)) {
            return [];
        }

        $directories = [];
        $items = scandir($basePath);

        if ($items === false) {
            return [];
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $basePath . '/' . $item;
            if (is_dir($fullPath)) {
                $directories[] = $fullPath;
            }
        }

        return $directories;
    }

    /**
     * Get controller subdirectory patterns.
     *
     * @return array<string>
     */
    private function getControllerSubPaths(): array
    {
        return $this->config['scan_paths']['controllers'] ?? ['Http/Controllers', 'Controllers'];
    }

    /**
     * Get model subdirectory patterns.
     *
     * @return array<string>
     */
    private function getModelSubPaths(): array
    {
        return $this->config['scan_paths']['models'] ?? ['Models', 'Entities'];
    }

    /**
     * Get all resolved paths as a flat array.
     *
     * @return array<string>
     */
    public function getAllPaths(): array
    {
        $resolved = $this->resolve();

        return array_merge(
            $resolved['controllers'],
            $resolved['models']
        );
    }
}
