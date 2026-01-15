<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Tests\Unit\Infrastructure;

use PHPUnit\Framework\TestCase;
use HerolabID\LaravelOpenApi\Infrastructure\Scanner\DirectoryScanner;

class DirectoryScannerTest extends TestCase
{
    private string $tempDir;
    private DirectoryScanner $scanner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . '/openapi_dirscanner_' . uniqid();
        mkdir($this->tempDir, 0755, true);

        $this->scanner = new DirectoryScanner();
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
        $files = $this->scanner->scan($this->tempDir);

        $this->assertIsArray($files);
        $this->assertEmpty($files);
    }

    /** @test */
    public function it_scans_php_files(): void
    {
        file_put_contents($this->tempDir . '/file1.php', '<?php');
        file_put_contents($this->tempDir . '/file2.php', '<?php');

        $files = $this->scanner->scan($this->tempDir);

        $this->assertCount(2, $files);
    }

    /** @test */
    public function it_respects_file_patterns(): void
    {
        file_put_contents($this->tempDir . '/file.php', '<?php');
        file_put_contents($this->tempDir . '/file.txt', 'text');

        $scanner = new DirectoryScanner([], ['*.php']);
        $files = $scanner->scan($this->tempDir);

        $this->assertCount(1, $files);
    }

    /** @test */
    public function it_supports_multiple_patterns(): void
    {
        file_put_contents($this->tempDir . '/file.php', '<?php');
        file_put_contents($this->tempDir . '/file.txt', 'text');
        file_put_contents($this->tempDir . '/file.md', '# Markdown');

        $scanner = new DirectoryScanner([], ['*.php', '*.txt']);
        $files = $scanner->scan($this->tempDir);

        $this->assertCount(2, $files);
    }

    /** @test */
    public function it_excludes_directories(): void
    {
        mkdir($this->tempDir . '/vendor', 0755, true);
        file_put_contents($this->tempDir . '/file.php', '<?php');
        file_put_contents($this->tempDir . '/vendor/package.php', '<?php');

        $scanner = new DirectoryScanner(['vendor']);
        $files = $scanner->scan($this->tempDir);

        $this->assertCount(1, $files);

        foreach ($files as $file) {
            $this->assertStringNotContainsString('vendor', $file);
        }
    }

    /** @test */
    public function it_scans_with_file_info(): void
    {
        file_put_contents($this->tempDir . '/file.php', '<?php echo "test";');

        $files = $this->scanner->scanWithInfo($this->tempDir);

        $this->assertCount(1, $files);
        $this->assertArrayHasKey('path', $files[0]);
        $this->assertArrayHasKey('size', $files[0]);
        $this->assertArrayHasKey('modified', $files[0]);
        $this->assertArrayHasKey('relative', $files[0]);

        $this->assertIsString($files[0]['path']);
        $this->assertIsInt($files[0]['size']);
        $this->assertIsInt($files[0]['modified']);
        $this->assertIsString($files[0]['relative']);
    }

    /** @test */
    public function it_gets_subdirectories(): void
    {
        mkdir($this->tempDir . '/dir1', 0755, true);
        mkdir($this->tempDir . '/dir2', 0755, true);
        mkdir($this->tempDir . '/dir3', 0755, true);

        $dirs = $this->scanner->getSubdirectories($this->tempDir);

        $this->assertCount(3, $dirs);
    }

    /** @test */
    public function it_excludes_subdirectories(): void
    {
        mkdir($this->tempDir . '/include', 0755, true);
        mkdir($this->tempDir . '/exclude', 0755, true);

        $scanner = new DirectoryScanner(['exclude']);
        $dirs = $scanner->getSubdirectories($this->tempDir);

        $this->assertCount(1, $dirs);

        foreach ($dirs as $dir) {
            $this->assertStringNotContainsString('exclude', $dir);
        }
    }

    /** @test */
    public function it_checks_if_directory_has_files(): void
    {
        $this->assertFalse($this->scanner->hasFiles($this->tempDir));

        file_put_contents($this->tempDir . '/file.php', '<?php');

        $this->assertTrue($this->scanner->hasFiles($this->tempDir));
    }

    /** @test */
    public function it_counts_files(): void
    {
        $this->assertEquals(0, $this->scanner->countFiles($this->tempDir));

        file_put_contents($this->tempDir . '/file1.php', '<?php');
        file_put_contents($this->tempDir . '/file2.php', '<?php');

        $this->assertEquals(2, $this->scanner->countFiles($this->tempDir));
    }

    /** @test */
    public function it_sets_max_depth(): void
    {
        mkdir($this->tempDir . '/level1', 0755, true);
        mkdir($this->tempDir . '/level1/level2', 0755, true);

        file_put_contents($this->tempDir . '/file0.php', '<?php');
        file_put_contents($this->tempDir . '/level1/file1.php', '<?php');
        file_put_contents($this->tempDir . '/level1/level2/file2.php', '<?php');

        $scanner = $this->scanner->setMaxDepth(1);
        $files = $scanner->scan($this->tempDir);

        // Should find file0.php and file1.php, but not file2.php
        $this->assertCount(2, $files);
    }

    /** @test */
    public function it_supports_fluent_interface(): void
    {
        $result = $this->scanner
            ->setMaxDepth(2)
            ->excludeDirectory('vendor')
            ->addPattern('*.php')
            ->followLinks(true);

        $this->assertInstanceOf(DirectoryScanner::class, $result);
    }

    /** @test */
    public function it_adds_patterns(): void
    {
        $this->scanner->addPattern('*.txt');

        file_put_contents($this->tempDir . '/file.php', '<?php');
        file_put_contents($this->tempDir . '/file.txt', 'text');

        $files = $this->scanner->scan($this->tempDir);

        $this->assertCount(2, $files);
    }

    /** @test */
    public function it_sets_patterns(): void
    {
        $this->scanner->setPatterns(['*.txt']);

        file_put_contents($this->tempDir . '/file.php', '<?php');
        file_put_contents($this->tempDir . '/file.txt', 'text');

        $files = $this->scanner->scan($this->tempDir);

        $this->assertCount(1, $files);

        foreach ($files as $file) {
            $this->assertStringEndsWith('.txt', $file);
        }
    }

    /** @test */
    public function it_gets_files_modified_after_timestamp(): void
    {
        $oldFile = $this->tempDir . '/old.php';
        file_put_contents($oldFile, '<?php');
        touch($oldFile, time() - 3600); // 1 hour ago

        sleep(1);
        $timestamp = time();
        sleep(1);

        $newFile = $this->tempDir . '/new.php';
        file_put_contents($newFile, '<?php');

        $files = $this->scanner->getModifiedAfter($this->tempDir, $timestamp);

        $this->assertCount(1, $files);
        $this->assertContains($newFile, $files);
    }

    /** @test */
    public function it_gets_files_larger_than_size(): void
    {
        // Create small file
        file_put_contents($this->tempDir . '/small.php', '<?php');

        // Create large file (> 1KB)
        file_put_contents($this->tempDir . '/large.php', str_repeat('<?php echo "x";', 100));

        $files = $this->scanner->getLargerThan($this->tempDir, '1K');

        $this->assertCount(1, $files);
    }

    /** @test */
    public function it_returns_config(): void
    {
        $scanner = new DirectoryScanner(['vendor'], ['*.php']);
        $scanner->setMaxDepth(5);
        $scanner->followLinks(true);

        $config = $scanner->getConfig();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('excludeDirs', $config);
        $this->assertArrayHasKey('filePatterns', $config);
        $this->assertArrayHasKey('maxDepth', $config);
        $this->assertArrayHasKey('followLinks', $config);

        $this->assertEquals(['vendor'], $config['excludeDirs']);
        $this->assertEquals(['*.php'], $config['filePatterns']);
        $this->assertEquals(5, $config['maxDepth']);
        $this->assertTrue($config['followLinks']);
    }

    /** @test */
    public function it_handles_non_existent_directory(): void
    {
        $files = $this->scanner->scan('/non/existent/path');

        $this->assertIsArray($files);
        $this->assertEmpty($files);
    }

    /** @test */
    public function it_sorts_files_by_name(): void
    {
        file_put_contents($this->tempDir . '/z.php', '<?php');
        file_put_contents($this->tempDir . '/a.php', '<?php');
        file_put_contents($this->tempDir . '/m.php', '<?php');

        $files = $this->scanner->scan($this->tempDir);

        $this->assertCount(3, $files);

        // Check files are sorted
        $basenames = array_map('basename', $files);
        $this->assertEquals(['a.php', 'm.php', 'z.php'], $basenames);
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
