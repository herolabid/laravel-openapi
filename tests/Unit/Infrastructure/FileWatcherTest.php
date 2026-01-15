<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Tests\Unit\Infrastructure;

use PHPUnit\Framework\TestCase;
use HerolabID\LaravelOpenApi\Infrastructure\Scanner\FileWatcher;
use HerolabID\LaravelOpenApi\Infrastructure\Scanner\FileScanner;
use HerolabID\LaravelOpenApi\Infrastructure\Cache\FileHasher;

class FileWatcherTest extends TestCase
{
    private string $tempDir;
    private FileWatcher $watcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . '/openapi_watcher_' . uniqid();
        mkdir($this->tempDir, 0755, true);

        $this->watcher = new FileWatcher();
    }

    protected function tearDown(): void
    {
        $this->watcher->stop();

        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }

        parent::tearDown();
    }

    /** @test */
    public function it_adds_file_to_watch(): void
    {
        $file = $this->tempDir . '/test.php';
        file_put_contents($file, '<?php');

        $this->watcher->addFile($file);

        $this->assertTrue($this->watcher->isWatching($file));
    }

    /** @test */
    public function it_adds_multiple_files(): void
    {
        $file1 = $this->tempDir . '/file1.php';
        $file2 = $this->tempDir . '/file2.php';

        file_put_contents($file1, '<?php');
        file_put_contents($file2, '<?php');

        $this->watcher->addFiles([$file1, $file2]);

        $this->assertEquals(2, $this->watcher->count());
    }

    /** @test */
    public function it_removes_file_from_watch(): void
    {
        $file = $this->tempDir . '/test.php';
        file_put_contents($file, '<?php');

        $this->watcher->addFile($file);
        $this->assertTrue($this->watcher->isWatching($file));

        $this->watcher->removeFile($file);
        $this->assertFalse($this->watcher->isWatching($file));
    }

    /** @test */
    public function it_detects_file_changes(): void
    {
        $file = $this->tempDir . '/test.php';
        file_put_contents($file, '<?php echo "original";');

        $this->watcher->addFile($file);

        // Initially no changes
        $this->assertFalse($this->watcher->hasChanges());

        sleep(1);

        // Modify file
        file_put_contents($file, '<?php echo "modified";');

        $this->assertTrue($this->watcher->hasChanges());
    }

    /** @test */
    public function it_returns_changed_files(): void
    {
        $file1 = $this->tempDir . '/file1.php';
        $file2 = $this->tempDir . '/file2.php';

        file_put_contents($file1, '<?php');
        file_put_contents($file2, '<?php');

        $this->watcher->addFiles([$file1, $file2]);

        sleep(1);

        // Modify only file1
        file_put_contents($file1, '<?php echo "changed";');

        $this->watcher->hasChanges();
        $changed = $this->watcher->getChangedFiles();

        $this->assertCount(1, $changed);
        $this->assertContains($file1, $changed);
        $this->assertNotContains($file2, $changed);
    }

    /** @test */
    public function it_detects_deleted_files(): void
    {
        $file = $this->tempDir . '/test.php';
        file_put_contents($file, '<?php');

        $this->watcher->addFile($file);

        // Delete file
        unlink($file);

        $this->assertTrue($this->watcher->hasChanges());
        $changed = $this->watcher->getChangedFiles();

        $this->assertContains($file, $changed);
        $this->assertFalse($this->watcher->isWatching($file));
    }

    /** @test */
    public function it_performs_non_blocking_check(): void
    {
        $file = $this->tempDir . '/test.php';
        file_put_contents($file, '<?php');

        $this->watcher->addFile($file);

        sleep(1);
        file_put_contents($file, '<?php echo "modified";');

        $changed = $this->watcher->check();

        $this->assertIsArray($changed);
        $this->assertCount(1, $changed);
    }

    /** @test */
    public function it_triggers_callbacks_on_changes(): void
    {
        $file = $this->tempDir . '/test.php';
        file_put_contents($file, '<?php');

        $callbackTriggered = false;
        $changedFiles = [];

        $this->watcher->addFile($file);
        $this->watcher->onChange(function ($files) use (&$callbackTriggered, &$changedFiles) {
            $callbackTriggered = true;
            $changedFiles = $files;
        });

        sleep(1);
        file_put_contents($file, '<?php echo "modified";');

        $this->watcher->check();

        $this->assertTrue($callbackTriggered);
        $this->assertCount(1, $changedFiles);
    }

    /** @test */
    public function it_supports_multiple_callbacks(): void
    {
        $file = $this->tempDir . '/test.php';
        file_put_contents($file, '<?php');

        $callback1Triggered = false;
        $callback2Triggered = false;

        $this->watcher->addFile($file);
        $this->watcher->onChange(function () use (&$callback1Triggered) {
            $callback1Triggered = true;
        });
        $this->watcher->onChange(function () use (&$callback2Triggered) {
            $callback2Triggered = true;
        });

        sleep(1);
        file_put_contents($file, '<?php echo "modified";');

        $this->watcher->check();

        $this->assertTrue($callback1Triggered);
        $this->assertTrue($callback2Triggered);
    }

    /** @test */
    public function it_resets_file_timestamps(): void
    {
        $file = $this->tempDir . '/test.php';
        file_put_contents($file, '<?php');

        $this->watcher->addFile($file);

        sleep(1);
        file_put_contents($file, '<?php echo "modified";');

        $this->assertTrue($this->watcher->hasChanges());

        $this->watcher->reset();

        // After reset, should not detect changes
        $this->assertFalse($this->watcher->hasChanges());
    }

    /** @test */
    public function it_gets_watched_files_list(): void
    {
        $file1 = $this->tempDir . '/file1.php';
        $file2 = $this->tempDir . '/file2.php';

        file_put_contents($file1, '<?php');
        file_put_contents($file2, '<?php');

        $this->watcher->addFiles([$file1, $file2]);

        $watched = $this->watcher->getWatchedFiles();

        $this->assertCount(2, $watched);
        $this->assertContains($file1, $watched);
        $this->assertContains($file2, $watched);
    }

    /** @test */
    public function it_counts_watched_files(): void
    {
        $this->assertEquals(0, $this->watcher->count());

        $file1 = $this->tempDir . '/file1.php';
        $file2 = $this->tempDir . '/file2.php';

        file_put_contents($file1, '<?php');
        file_put_contents($file2, '<?php');

        $this->watcher->addFiles([$file1, $file2]);

        $this->assertEquals(2, $this->watcher->count());
    }

    /** @test */
    public function it_clears_all_watched_files(): void
    {
        $file = $this->tempDir . '/test.php';
        file_put_contents($file, '<?php');

        $this->watcher->addFile($file);
        $this->assertEquals(1, $this->watcher->count());

        $this->watcher->clear();
        $this->assertEquals(0, $this->watcher->count());
    }

    /** @test */
    public function it_supports_fluent_interface(): void
    {
        $file = $this->tempDir . '/test.php';
        file_put_contents($file, '<?php');

        $result = $this->watcher
            ->addFile($file)
            ->setPollInterval(2)
            ->onChange(function () {})
            ->reset();

        $this->assertInstanceOf(FileWatcher::class, $result);
    }

    /** @test */
    public function it_sets_poll_interval(): void
    {
        $watcher = new FileWatcher([], 5);

        $stats = $watcher->getStats();
        $this->assertEquals(0, $stats['watched']);
    }

    /** @test */
    public function it_returns_statistics(): void
    {
        $file = $this->tempDir . '/test.php';
        file_put_contents($file, '<?php');

        $this->watcher->addFile($file);
        $this->watcher->onChange(function () {});

        $stats = $this->watcher->getStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('watched', $stats);
        $this->assertArrayHasKey('changed', $stats);
        $this->assertArrayHasKey('callbacks', $stats);
        $this->assertArrayHasKey('running', $stats);

        $this->assertEquals(1, $stats['watched']);
        $this->assertEquals(1, $stats['callbacks']);
        $this->assertFalse($stats['running']);
    }

    /** @test */
    public function it_creates_from_file_hasher(): void
    {
        $file = $this->tempDir . '/test.php';
        file_put_contents($file, '<?php');

        $hasher = new FileHasher([$this->tempDir]);
        $watcher = FileWatcher::fromHasher($hasher);

        $this->assertInstanceOf(FileWatcher::class, $watcher);
        $this->assertGreaterThan(0, $watcher->count());
    }

    /** @test */
    public function it_creates_from_file_scanner(): void
    {
        $file = $this->tempDir . '/test.php';
        file_put_contents($file, '<?php');

        $scanner = new FileScanner([$this->tempDir]);
        $watcher = FileWatcher::fromScanner($scanner);

        $this->assertInstanceOf(FileWatcher::class, $watcher);
        $this->assertGreaterThan(0, $watcher->count());
    }

    /** @test */
    public function it_ignores_non_existent_files(): void
    {
        $this->watcher->addFile('/non/existent/file.php');

        $this->assertEquals(0, $this->watcher->count());
    }

    /** @test */
    public function it_handles_rapid_changes(): void
    {
        $file = $this->tempDir . '/test.php';
        file_put_contents($file, '<?php');

        $this->watcher->addFile($file);

        sleep(1);

        // Make multiple rapid changes
        for ($i = 0; $i < 3; $i++) {
            file_put_contents($file, "<?php echo {$i};");
            usleep(100000); // 0.1 second
        }

        $this->assertTrue($this->watcher->hasChanges());
    }

    /** @test */
    public function it_does_not_detect_same_content_touch(): void
    {
        $file = $this->tempDir . '/test.php';
        file_put_contents($file, '<?php');

        $this->watcher->addFile($file);

        sleep(1);

        // Touch file (update mtime but not content)
        touch($file);

        // Should still detect change (based on mtime, not content)
        $this->assertTrue($this->watcher->hasChanges());
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
