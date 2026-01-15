<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Infrastructure\Scanner;

use HerolabID\LaravelOpenApi\Infrastructure\Cache\FileHasher;

/**
 * Watch files for changes and trigger callbacks.
 *
 * Uses polling mechanism for cross-platform compatibility.
 * For hot reload support in development mode.
 */
class FileWatcher
{
    /**
     * @var array<string, int>
     */
    private array $watchedFiles = [];

    /**
     * @var array<callable>
     */
    private array $changeCallbacks = [];

    /**
     * @var int
     */
    private int $pollInterval = 1;

    /**
     * @var bool
     */
    private bool $isRunning = false;

    /**
     * @var array<string>
     */
    private array $changedFiles = [];

    /**
     * @param array<string> $files
     * @param int $pollInterval Poll interval in seconds
     */
    public function __construct(
        array $files = [],
        int $pollInterval = 1,
    ) {
        $this->pollInterval = $pollInterval;
        $this->addFiles($files);
    }

    /**
     * Add file to watch list.
     */
    public function addFile(string $file): self
    {
        if (file_exists($file)) {
            $this->watchedFiles[$file] = filemtime($file);
        }

        return $this;
    }

    /**
     * Add multiple files to watch list.
     *
     * @param array<string> $files
     */
    public function addFiles(array $files): self
    {
        foreach ($files as $file) {
            $this->addFile($file);
        }

        return $this;
    }

    /**
     * Remove file from watch list.
     */
    public function removeFile(string $file): self
    {
        unset($this->watchedFiles[$file]);

        return $this;
    }

    /**
     * Add callback to execute when files change.
     *
     * Callback signature: function(array $changedFiles): void
     *
     * @param callable(array<string>): void $callback
     */
    public function onChange(callable $callback): self
    {
        $this->changeCallbacks[] = $callback;

        return $this;
    }

    /**
     * Set poll interval in seconds.
     */
    public function setPollInterval(int $seconds): self
    {
        $this->pollInterval = max(1, $seconds);

        return $this;
    }

    /**
     * Check if any watched files have changed.
     */
    public function hasChanges(): bool
    {
        $this->changedFiles = [];

        foreach ($this->watchedFiles as $file => $lastModified) {
            if (!file_exists($file)) {
                // File was deleted
                $this->changedFiles[] = $file;
                unset($this->watchedFiles[$file]);
                continue;
            }

            $currentModified = filemtime($file);

            if ($currentModified > $lastModified) {
                $this->changedFiles[] = $file;
                $this->watchedFiles[$file] = $currentModified;
            }
        }

        return !empty($this->changedFiles);
    }

    /**
     * Get list of changed files since last check.
     *
     * @return array<string>
     */
    public function getChangedFiles(): array
    {
        return $this->changedFiles;
    }

    /**
     * Start watching files (blocking).
     *
     * This method blocks and polls for changes indefinitely.
     * Use check() for non-blocking single checks.
     */
    public function watch(): void
    {
        $this->isRunning = true;

        while ($this->isRunning) {
            if ($this->hasChanges()) {
                $this->triggerCallbacks($this->changedFiles);
            }

            sleep($this->pollInterval);
        }
    }

    /**
     * Stop watching.
     */
    public function stop(): void
    {
        $this->isRunning = false;
    }

    /**
     * Perform single check for changes (non-blocking).
     *
     * @return array<string> Changed files
     */
    public function check(): array
    {
        if ($this->hasChanges()) {
            $this->triggerCallbacks($this->changedFiles);

            return $this->changedFiles;
        }

        return [];
    }

    /**
     * Reset watched files timestamps to current.
     */
    public function reset(): self
    {
        foreach ($this->watchedFiles as $file => $timestamp) {
            if (file_exists($file)) {
                $this->watchedFiles[$file] = filemtime($file);
            }
        }

        $this->changedFiles = [];

        return $this;
    }

    /**
     * Get list of watched files.
     *
     * @return array<string>
     */
    public function getWatchedFiles(): array
    {
        return array_keys($this->watchedFiles);
    }

    /**
     * Get count of watched files.
     */
    public function count(): int
    {
        return count($this->watchedFiles);
    }

    /**
     * Check if file is being watched.
     */
    public function isWatching(string $file): bool
    {
        return isset($this->watchedFiles[$file]);
    }

    /**
     * Create watcher from FileHasher tracked files.
     */
    public static function fromHasher(FileHasher $hasher, int $pollInterval = 1): self
    {
        return new self($hasher->getTrackedFiles(), $pollInterval);
    }

    /**
     * Create watcher from FileScanner.
     */
    public static function fromScanner(FileScanner $scanner, int $pollInterval = 1): self
    {
        return new self($scanner->getRelevantFiles(), $pollInterval);
    }

    /**
     * Watch with callback (convenience method).
     *
     * @param array<string> $files
     * @param callable(array<string>): void $callback
     */
    public static function watchFiles(
        array $files,
        callable $callback,
        int $pollInterval = 1
    ): self {
        $watcher = new self($files, $pollInterval);
        $watcher->onChange($callback);
        $watcher->watch();

        return $watcher;
    }

    /**
     * Trigger all registered callbacks.
     *
     * @param array<string> $changedFiles
     */
    private function triggerCallbacks(array $changedFiles): void
    {
        foreach ($this->changeCallbacks as $callback) {
            $callback($changedFiles);
        }
    }

    /**
     * Clear all watched files.
     */
    public function clear(): self
    {
        $this->watchedFiles = [];
        $this->changedFiles = [];

        return $this;
    }

    /**
     * Get watcher statistics.
     *
     * @return array{watched: int, changed: int, callbacks: int, running: bool}
     */
    public function getStats(): array
    {
        return [
            'watched' => count($this->watchedFiles),
            'changed' => count($this->changedFiles),
            'callbacks' => count($this->changeCallbacks),
            'running' => $this->isRunning,
        ];
    }
}
