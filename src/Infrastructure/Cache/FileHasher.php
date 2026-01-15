<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Infrastructure\Cache;

use Symfony\Component\Finder\Finder;

/**
 * Calculate hash of files for cache invalidation.
 *
 * Uses MD5 hash + file modification time for efficient change detection.
 */
class FileHasher
{
    /**
     * @var array<string>
     */
    private array $trackedFiles = [];

    /**
     * @var array<string>
     */
    private array $paths = [];

    /**
     * @param array<string> $paths Paths to track
     * @param array<string> $exclude Patterns to exclude
     */
    public function __construct(
        array $paths = [],
        private readonly array $exclude = [],
    ) {
        $this->paths = $paths;
    }

    /**
     * Calculate hash of all tracked files.
     */
    public function calculate(): string
    {
        $this->scanFiles();

        if (empty($this->trackedFiles)) {
            return md5('empty');
        }

        $hashes = [];

        foreach ($this->trackedFiles as $file) {
            if (!file_exists($file)) {
                continue;
            }

            // Use mtime + file size for quick check
            $mtime = filemtime($file);
            $size = filesize($file);

            // For more accuracy, we could md5_file here
            // but mtime + size is faster and usually sufficient
            $hashes[] = "{$file}:{$mtime}:{$size}";
        }

        // Sort for consistent hashing
        sort($hashes);

        return md5(implode('|', $hashes));
    }

    /**
     * Calculate detailed hash (slower but more accurate).
     * Uses MD5 of file contents.
     */
    public function calculateDetailed(): string
    {
        $this->scanFiles();

        if (empty($this->trackedFiles)) {
            return md5('empty');
        }

        $hashes = [];

        foreach ($this->trackedFiles as $file) {
            if (!file_exists($file)) {
                continue;
            }

            // MD5 of file contents
            $hashes[] = md5_file($file);
        }

        // Sort for consistent hashing
        sort($hashes);

        return md5(implode('|', $hashes));
    }

    /**
     * Check if files have changed since last check.
     */
    public function hasChanged(string $previousHash): bool
    {
        $currentHash = $this->calculate();
        return $currentHash !== $previousHash;
    }

    /**
     * Add path to track.
     */
    public function addPath(string $path): self
    {
        if (!in_array($path, $this->paths, true)) {
            $this->paths[] = $path;
        }

        return $this;
    }

    /**
     * Add multiple paths to track.
     *
     * @param array<string> $paths
     */
    public function addPaths(array $paths): self
    {
        foreach ($paths as $path) {
            $this->addPath($path);
        }

        return $this;
    }

    /**
     * Get tracked files.
     *
     * @return array<string>
     */
    public function getTrackedFiles(): array
    {
        if (empty($this->trackedFiles)) {
            $this->scanFiles();
        }

        return $this->trackedFiles;
    }

    /**
     * Scan files from configured paths.
     */
    private function scanFiles(): void
    {
        $this->trackedFiles = [];

        foreach ($this->paths as $path) {
            if (is_file($path)) {
                $this->trackedFiles[] = $path;
                continue;
            }

            if (is_dir($path)) {
                $this->scanDirectory($path);
            }
        }
    }

    /**
     * Scan a directory for PHP files.
     */
    private function scanDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $finder = new Finder();
        $finder->files()
            ->in($directory)
            ->name('*.php')
            ->ignoreVCS(true)
            ->ignoreUnreadableDirs();

        // Apply exclusions
        foreach ($this->exclude as $pattern) {
            $finder->notPath($pattern);
        }

        foreach ($finder as $file) {
            $this->trackedFiles[] = $file->getRealPath();
        }
    }

    /**
     * Get hash for specific files.
     *
     * @param array<string> $files
     */
    public function hashFiles(array $files): string
    {
        $hashes = [];

        foreach ($files as $file) {
            if (!file_exists($file)) {
                continue;
            }

            $mtime = filemtime($file);
            $size = filesize($file);
            $hashes[] = "{$file}:{$mtime}:{$size}";
        }

        sort($hashes);

        return md5(implode('|', $hashes));
    }

    /**
     * Get changed files since last hash.
     *
     * @param array<string, string> $previousHashes Map of file => hash
     * @return array<string>
     */
    public function getChangedFiles(array $previousHashes): array
    {
        $this->scanFiles();
        $changed = [];

        foreach ($this->trackedFiles as $file) {
            if (!file_exists($file)) {
                continue;
            }

            $currentHash = md5_file($file);
            $previousHash = $previousHashes[$file] ?? null;

            if ($previousHash === null || $currentHash !== $previousHash) {
                $changed[] = $file;
            }
        }

        return $changed;
    }

    /**
     * Create file hash map.
     *
     * @return array<string, string>
     */
    public function createHashMap(): array
    {
        $this->scanFiles();
        $map = [];

        foreach ($this->trackedFiles as $file) {
            if (!file_exists($file)) {
                continue;
            }

            $map[$file] = md5_file($file);
        }

        return $map;
    }
}
