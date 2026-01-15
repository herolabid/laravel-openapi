<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Tests\Unit\Infrastructure;

use PHPUnit\Framework\TestCase;
use HerolabID\LaravelOpenApi\Infrastructure\Cache\FileHasher;

class FileHasherTest extends TestCase
{
    private string $tempDir;
    private FileHasher $hasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . '/openapi_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);

        $this->hasher = new FileHasher([$this->tempDir]);
    }

    protected function tearDown(): void
    {
        // Clean up temp files
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }

        parent::tearDown();
    }

    /** @test */
    public function it_calculates_hash_for_empty_directory(): void
    {
        $hash = $this->hasher->calculate();

        $this->assertIsString($hash);
        $this->assertEquals(32, strlen($hash)); // MD5 length
    }

    /** @test */
    public function it_calculates_hash_for_files(): void
    {
        // Create test file
        file_put_contents($this->tempDir . '/test.php', '<?php echo "test";');

        $hash1 = $this->hasher->calculate();
        $this->assertIsString($hash1);

        // Hash should be consistent
        $hash2 = $this->hasher->calculate();
        $this->assertEquals($hash1, $hash2);
    }

    /** @test */
    public function it_detects_file_changes(): void
    {
        $file = $this->tempDir . '/test.php';
        file_put_contents($file, '<?php echo "test";');

        $hash1 = $this->hasher->calculate();

        // Wait to ensure different mtime
        sleep(1);

        // Modify file
        file_put_contents($file, '<?php echo "modified";');

        $hash2 = $this->hasher->calculate();

        $this->assertNotEquals($hash1, $hash2);
    }

    /** @test */
    public function it_detects_new_files(): void
    {
        $hash1 = $this->hasher->calculate();

        // Add new file
        file_put_contents($this->tempDir . '/new.php', '<?php');

        $hash2 = $this->hasher->calculate();

        $this->assertNotEquals($hash1, $hash2);
    }

    /** @test */
    public function it_tracks_multiple_files(): void
    {
        file_put_contents($this->tempDir . '/file1.php', '<?php');
        file_put_contents($this->tempDir . '/file2.php', '<?php');

        $files = $this->hasher->getTrackedFiles();

        $this->assertCount(2, $files);
    }

    /** @test */
    public function it_can_add_paths(): void
    {
        $hasher = new FileHasher();

        $hasher->addPath($this->tempDir);
        $hasher->addPath(__FILE__);

        $files = $hasher->getTrackedFiles();

        $this->assertNotEmpty($files);
    }

    /** @test */
    public function it_creates_hash_map(): void
    {
        file_put_contents($this->tempDir . '/test.php', '<?php');

        $map = $this->hasher->createHashMap();

        $this->assertIsArray($map);
        $this->assertNotEmpty($map);

        foreach ($map as $file => $hash) {
            $this->assertIsString($file);
            $this->assertIsString($hash);
            $this->assertEquals(32, strlen($hash));
        }
    }

    /** @test */
    public function it_detects_changed_files(): void
    {
        $file1 = $this->tempDir . '/file1.php';
        $file2 = $this->tempDir . '/file2.php';

        file_put_contents($file1, '<?php echo "1";');
        file_put_contents($file2, '<?php echo "2";');

        $oldMap = $this->hasher->createHashMap();

        // Modify one file
        sleep(1);
        file_put_contents($file1, '<?php echo "modified";');

        $changed = $this->hasher->getChangedFiles($oldMap);

        $this->assertCount(1, $changed);
        $this->assertContains($file1, $changed);
        $this->assertNotContains($file2, $changed);
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
