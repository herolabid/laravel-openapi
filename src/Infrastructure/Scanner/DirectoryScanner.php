<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Infrastructure\Scanner;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Advanced directory scanner with filtering and depth control.
 *
 * Provides low-level directory traversal operations with various filters.
 */
class DirectoryScanner
{
    /**
     * @var array<string>
     */
    private array $excludeDirs = [];

    /**
     * @var array<string>
     */
    private array $filePatterns = ['*.php'];

    /**
     * @var int|null
     */
    private ?int $maxDepth = null;

    /**
     * @var bool
     */
    private bool $followLinks = false;

    /**
     * @param array<string> $excludeDirs
     * @param array<string> $filePatterns
     */
    public function __construct(
        array $excludeDirs = [],
        array $filePatterns = ['*.php'],
    ) {
        $this->excludeDirs = $excludeDirs;
        $this->filePatterns = $filePatterns;
    }

    /**
     * Scan directory and return file paths.
     *
     * @return array<string>
     */
    public function scan(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $finder = $this->createFinder();
        $finder->in($directory);

        $files = [];

        foreach ($finder as $file) {
            $files[] = $file->getRealPath();
        }

        return $files;
    }

    /**
     * Scan directory and return file info.
     *
     * @return array<array{path: string, size: int, modified: int, relative: string}>
     */
    public function scanWithInfo(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $finder = $this->createFinder();
        $finder->in($directory);

        $files = [];

        foreach ($finder as $file) {
            $files[] = [
                'path' => $file->getRealPath(),
                'size' => $file->getSize(),
                'modified' => $file->getMTime(),
                'relative' => $file->getRelativePathname(),
            ];
        }

        return $files;
    }

    /**
     * Get subdirectories of a directory.
     *
     * @return array<string>
     */
    public function getSubdirectories(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $finder = new Finder();
        $finder->directories()
            ->in($directory)
            ->depth(0)
            ->ignoreVCS(true)
            ->ignoreUnreadableDirs()
            ->sortByName();

        // Apply directory exclusions
        foreach ($this->excludeDirs as $exclude) {
            $finder->exclude($exclude);
        }

        $directories = [];

        foreach ($finder as $dir) {
            $directories[] = $dir->getRealPath();
        }

        return $directories;
    }

    /**
     * Check if directory contains any matching files.
     */
    public function hasFiles(string $directory): bool
    {
        if (!is_dir($directory)) {
            return false;
        }

        $finder = $this->createFinder();
        $finder->in($directory);

        return $finder->hasResults();
    }

    /**
     * Count files in directory.
     */
    public function countFiles(string $directory): int
    {
        if (!is_dir($directory)) {
            return 0;
        }

        $finder = $this->createFinder();
        $finder->in($directory);

        return $finder->count();
    }

    /**
     * Set maximum depth for recursive scanning.
     *
     * @param int|null $depth Null for unlimited depth
     */
    public function setMaxDepth(?int $depth): self
    {
        $this->maxDepth = $depth;

        return $this;
    }

    /**
     * Add directory to exclude list.
     */
    public function excludeDirectory(string $directory): self
    {
        if (!in_array($directory, $this->excludeDirs, true)) {
            $this->excludeDirs[] = $directory;
        }

        return $this;
    }

    /**
     * Add file pattern to match.
     */
    public function addPattern(string $pattern): self
    {
        if (!in_array($pattern, $this->filePatterns, true)) {
            $this->filePatterns[] = $pattern;
        }

        return $this;
    }

    /**
     * Set file patterns to match.
     *
     * @param array<string> $patterns
     */
    public function setPatterns(array $patterns): self
    {
        $this->filePatterns = $patterns;

        return $this;
    }

    /**
     * Enable or disable following symbolic links.
     */
    public function followLinks(bool $follow = true): self
    {
        $this->followLinks = $follow;

        return $this;
    }

    /**
     * Get files modified after a specific timestamp.
     *
     * @return array<string>
     */
    public function getModifiedAfter(string $directory, int $timestamp): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $finder = $this->createFinder();
        $finder->in($directory)
            ->date(">= @{$timestamp}");

        $files = [];

        foreach ($finder as $file) {
            $files[] = $file->getRealPath();
        }

        return $files;
    }

    /**
     * Get files larger than specific size.
     *
     * @param string $size Size string (e.g., '10K', '1M')
     * @return array<string>
     */
    public function getLargerThan(string $directory, string $size): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $finder = $this->createFinder();
        $finder->in($directory)
            ->size(">= {$size}");

        $files = [];

        foreach ($finder as $file) {
            $files[] = $file->getRealPath();
        }

        return $files;
    }

    /**
     * Create configured Finder instance.
     */
    private function createFinder(): Finder
    {
        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->ignoreUnreadableDirs()
            ->sortByName();

        // Apply file patterns
        foreach ($this->filePatterns as $pattern) {
            $finder->name($pattern);
        }

        // Apply directory exclusions
        foreach ($this->excludeDirs as $exclude) {
            $finder->exclude($exclude);
        }

        // Apply depth limit
        if ($this->maxDepth !== null) {
            $finder->depth("<= {$this->maxDepth}");
        }

        // Follow links if enabled
        if ($this->followLinks) {
            $finder->followLinks();
        }

        return $finder;
    }

    /**
     * Get current configuration.
     *
     * @return array{excludeDirs: array<string>, filePatterns: array<string>, maxDepth: int|null, followLinks: bool}
     */
    public function getConfig(): array
    {
        return [
            'excludeDirs' => $this->excludeDirs,
            'filePatterns' => $this->filePatterns,
            'maxDepth' => $this->maxDepth,
            'followLinks' => $this->followLinks,
        ];
    }
}
