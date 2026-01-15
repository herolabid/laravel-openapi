<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Tests\Unit\Infrastructure;

use PHPUnit\Framework\TestCase;
use HerolabID\LaravelOpenApi\Infrastructure\Scanner\ModulePathResolver;

class ModulePathResolverTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . '/openapi_modules_' . uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }

        parent::tearDown();
    }

    /** @test */
    public function it_returns_empty_when_disabled(): void
    {
        $resolver = new ModulePathResolver([
            'enabled' => false,
        ]);

        $paths = $resolver->resolve();

        $this->assertEmpty($paths['controllers']);
        $this->assertEmpty($paths['models']);
    }

    /** @test */
    public function it_resolves_custom_module_paths(): void
    {
        // Create module structure
        $modulesPath = $this->tempDir . '/modules';
        $userModule = $modulesPath . '/User';
        $postModule = $modulesPath . '/Post';

        mkdir($userModule . '/Http/Controllers', 0755, true);
        mkdir($userModule . '/Models', 0755, true);
        mkdir($postModule . '/Http/Controllers', 0755, true);
        mkdir($postModule . '/Models', 0755, true);

        $resolver = new ModulePathResolver([
            'enabled' => true,
            'auto_detect' => false,
            'paths' => [$modulesPath],
            'scan_paths' => [
                'controllers' => ['Http/Controllers'],
                'models' => ['Models'],
            ],
        ]);

        $paths = $resolver->resolve();

        $this->assertCount(2, $paths['controllers']);
        $this->assertCount(2, $paths['models']);
        $this->assertContains($userModule . '/Http/Controllers', $paths['controllers']);
        $this->assertContains($postModule . '/Http/Controllers', $paths['controllers']);
        $this->assertContains($userModule . '/Models', $paths['models']);
        $this->assertContains($postModule . '/Models', $paths['models']);
    }

    /** @test */
    public function it_supports_multiple_subdirectory_patterns(): void
    {
        // Create module with alternative structure
        $modulesPath = $this->tempDir . '/modules';
        $userModule = $modulesPath . '/User';

        mkdir($userModule . '/Controllers', 0755, true);
        mkdir($userModule . '/Entities', 0755, true);

        $resolver = new ModulePathResolver([
            'enabled' => true,
            'auto_detect' => false,
            'paths' => [$modulesPath],
            'scan_paths' => [
                'controllers' => ['Http/Controllers', 'Controllers'],
                'models' => ['Models', 'Entities'],
            ],
        ]);

        $paths = $resolver->resolve();

        $this->assertContains($userModule . '/Controllers', $paths['controllers']);
        $this->assertContains($userModule . '/Entities', $paths['models']);
    }

    /** @test */
    public function it_skips_non_existent_paths(): void
    {
        $nonExistentPath = $this->tempDir . '/non-existent';

        $resolver = new ModulePathResolver([
            'enabled' => true,
            'auto_detect' => false,
            'paths' => [$nonExistentPath],
        ]);

        $paths = $resolver->resolve();

        $this->assertEmpty($paths['controllers']);
        $this->assertEmpty($paths['models']);
    }

    /** @test */
    public function it_skips_non_existent_subdirectories(): void
    {
        // Create module without Models directory
        $modulesPath = $this->tempDir . '/modules';
        $userModule = $modulesPath . '/User';

        mkdir($userModule . '/Http/Controllers', 0755, true);
        // Intentionally not creating Models directory

        $resolver = new ModulePathResolver([
            'enabled' => true,
            'auto_detect' => false,
            'paths' => [$modulesPath],
            'scan_paths' => [
                'controllers' => ['Http/Controllers'],
                'models' => ['Models'],
            ],
        ]);

        $paths = $resolver->resolve();

        $this->assertCount(1, $paths['controllers']);
        $this->assertEmpty($paths['models']);
    }

    /** @test */
    public function it_returns_all_paths_as_flat_array(): void
    {
        // Create module structure
        $modulesPath = $this->tempDir . '/modules';
        $userModule = $modulesPath . '/User';

        mkdir($userModule . '/Http/Controllers', 0755, true);
        mkdir($userModule . '/Models', 0755, true);

        $resolver = new ModulePathResolver([
            'enabled' => true,
            'auto_detect' => false,
            'paths' => [$modulesPath],
            'scan_paths' => [
                'controllers' => ['Http/Controllers'],
                'models' => ['Models'],
            ],
        ]);

        $allPaths = $resolver->getAllPaths();

        $this->assertCount(2, $allPaths);
        $this->assertContains($userModule . '/Http/Controllers', $allPaths);
        $this->assertContains($userModule . '/Models', $allPaths);
    }

    /** @test */
    public function it_handles_empty_module_directories(): void
    {
        // Create modules path but no modules inside
        $modulesPath = $this->tempDir . '/modules';
        mkdir($modulesPath, 0755, true);

        $resolver = new ModulePathResolver([
            'enabled' => true,
            'auto_detect' => false,
            'paths' => [$modulesPath],
        ]);

        $paths = $resolver->resolve();

        $this->assertEmpty($paths['controllers']);
        $this->assertEmpty($paths['models']);
    }

    /**
     * Recursively remove directory.
     */
    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir) ?: [], ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . '/' . $file;

            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
