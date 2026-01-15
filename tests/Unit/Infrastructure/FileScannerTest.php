<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Tests\Unit\Infrastructure;

use PHPUnit\Framework\TestCase;
use HerolabID\LaravelOpenApi\Infrastructure\Scanner\FileScanner;

class FileScannerTest extends TestCase
{
    private string $tempDir;
    private string $controllerDir;
    private string $modelDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . '/openapi_scanner_' . uniqid();
        $this->controllerDir = $this->tempDir . '/Controllers';
        $this->modelDir = $this->tempDir . '/Models';

        mkdir($this->controllerDir, 0755, true);
        mkdir($this->modelDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }

        parent::tearDown();
    }

    /** @test */
    public function it_scans_empty_directory(): void
    {
        $scanner = new FileScanner([$this->controllerDir]);

        $files = $scanner->scan([$this->controllerDir]);

        $this->assertIsArray($files);
        $this->assertEmpty($files);
    }

    /** @test */
    public function it_scans_php_files(): void
    {
        // Create test files
        file_put_contents($this->controllerDir . '/UserController.php', '<?php');
        file_put_contents($this->controllerDir . '/PostController.php', '<?php');

        $scanner = new FileScanner();
        $files = $scanner->scan([$this->controllerDir]);

        $this->assertCount(2, $files);
    }

    /** @test */
    public function it_ignores_non_php_files(): void
    {
        file_put_contents($this->controllerDir . '/Controller.php', '<?php');
        file_put_contents($this->controllerDir . '/readme.txt', 'readme');
        file_put_contents($this->controllerDir . '/config.json', '{}');

        $scanner = new FileScanner();
        $files = $scanner->scan([$this->controllerDir]);

        $this->assertCount(1, $files);
    }

    /** @test */
    public function it_scans_nested_directories(): void
    {
        mkdir($this->controllerDir . '/Api', 0755, true);
        file_put_contents($this->controllerDir . '/Controller.php', '<?php');
        file_put_contents($this->controllerDir . '/Api/UserController.php', '<?php');

        $scanner = new FileScanner();
        $files = $scanner->scan([$this->controllerDir]);

        $this->assertCount(2, $files);
    }

    /** @test */
    public function it_scans_single_file(): void
    {
        $file = $this->controllerDir . '/Controller.php';
        file_put_contents($file, '<?php');

        $scanner = new FileScanner();
        $files = $scanner->scan([$file]);

        $this->assertCount(1, $files);
        $this->assertEquals($file, $files[0]);
    }

    /** @test */
    public function it_removes_duplicates(): void
    {
        file_put_contents($this->controllerDir . '/Controller.php', '<?php');

        $scanner = new FileScanner();
        $files = $scanner->scan([
            $this->controllerDir,
            $this->controllerDir, // Duplicate path
        ]);

        $this->assertCount(1, $files);
    }

    /** @test */
    public function it_gets_relevant_files_from_controllers_and_models(): void
    {
        file_put_contents($this->controllerDir . '/UserController.php', '<?php');
        file_put_contents($this->modelDir . '/User.php', '<?php');

        $scanner = new FileScanner(
            [$this->controllerDir],
            [$this->modelDir]
        );

        $files = $scanner->getRelevantFiles();

        $this->assertCount(2, $files);
    }

    /** @test */
    public function it_caches_relevant_files(): void
    {
        file_put_contents($this->controllerDir . '/Controller.php', '<?php');

        $scanner = new FileScanner([$this->controllerDir]);

        // First call
        $files1 = $scanner->getRelevantFiles();

        // Add new file (should not be picked up due to cache)
        file_put_contents($this->controllerDir . '/NewController.php', '<?php');

        // Second call (cached)
        $files2 = $scanner->getRelevantFiles();

        $this->assertEquals($files1, $files2);
        $this->assertCount(1, $files2);
    }

    /** @test */
    public function it_clears_cache(): void
    {
        file_put_contents($this->controllerDir . '/Controller.php', '<?php');

        $scanner = new FileScanner([$this->controllerDir]);

        $files1 = $scanner->getRelevantFiles();

        // Add new file
        file_put_contents($this->controllerDir . '/NewController.php', '<?php');

        // Clear cache and scan again
        $scanner->clearCache();
        $files2 = $scanner->getRelevantFiles();

        $this->assertNotEquals(count($files1), count($files2));
        $this->assertCount(2, $files2);
    }

    /** @test */
    public function it_adds_controller_path(): void
    {
        $scanner = new FileScanner();

        $scanner->addControllerPath($this->controllerDir);

        $this->assertContains($this->controllerDir, $scanner->getControllerPaths());
    }

    /** @test */
    public function it_adds_model_path(): void
    {
        $scanner = new FileScanner();

        $scanner->addModelPath($this->modelDir);

        $this->assertContains($this->modelDir, $scanner->getModelPaths());
    }

    /** @test */
    public function it_supports_fluent_interface(): void
    {
        $scanner = new FileScanner();

        $result = $scanner
            ->addControllerPath($this->controllerDir)
            ->addModelPath($this->modelDir)
            ->clearCache();

        $this->assertInstanceOf(FileScanner::class, $result);
    }

    /** @test */
    public function it_filters_excluded_patterns(): void
    {
        file_put_contents($this->controllerDir . '/UserController.php', '<?php');
        file_put_contents($this->controllerDir . '/AdminController.php', '<?php');
        file_put_contents($this->controllerDir . '/TestController.php', '<?php');

        $scanner = new FileScanner(
            [$this->controllerDir],
            [],
            ['*Test*'] // Exclude Test files
        );

        $files = $scanner->getRelevantFiles();

        $this->assertCount(2, $files);

        foreach ($files as $file) {
            $this->assertStringNotContainsString('Test', $file);
        }
    }

    /** @test */
    public function it_adds_exclude_pattern(): void
    {
        $scanner = new FileScanner();

        $scanner->addExcludePattern('*Test*');

        $this->assertContains('*Test*', $scanner->getExcludePatterns());
    }

    /** @test */
    public function it_excludes_with_string_matching(): void
    {
        file_put_contents($this->controllerDir . '/Controller.php', '<?php');
        mkdir($this->controllerDir . '/vendor', 0755, true);
        file_put_contents($this->controllerDir . '/vendor/Package.php', '<?php');

        $scanner = new FileScanner(
            [$this->controllerDir],
            [],
            ['vendor']
        );

        $files = $scanner->getRelevantFiles();

        $this->assertCount(1, $files);

        foreach ($files as $file) {
            $this->assertStringNotContainsString('vendor', $file);
        }
    }

    /** @test */
    public function it_invalidates_cache_when_adding_paths(): void
    {
        file_put_contents($this->controllerDir . '/Controller.php', '<?php');

        $scanner = new FileScanner([$this->controllerDir]);

        // Cache the files
        $files1 = $scanner->getRelevantFiles();

        // Add new path (should invalidate cache)
        file_put_contents($this->modelDir . '/User.php', '<?php');
        $scanner->addModelPath($this->modelDir);

        $files2 = $scanner->getRelevantFiles();

        $this->assertNotEquals(count($files1), count($files2));
    }

    /** @test */
    public function it_handles_non_existent_paths(): void
    {
        $scanner = new FileScanner(['/non/existent/path']);

        $files = $scanner->getRelevantFiles();

        $this->assertIsArray($files);
        $this->assertEmpty($files);
    }

    private function removeDirectory(string $dir): void
    {
        $files = array_diff(scandir($dir), ['.', '..']);

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
