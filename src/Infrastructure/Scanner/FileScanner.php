<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Infrastructure\Scanner;

use HerolabID\LaravelOpenApi\Contracts\ScannerInterface;
use Symfony\Component\Finder\Finder;

/**
 * Scan PHP files for OpenAPI attributes.
 *
 * Scans controllers and models following Laravel conventions.
 */
class FileScanner implements ScannerInterface
{
    /**
     * @var array<string>
     */
    private array $controllerPaths = [];

    /**
     * @var array<string>
     */
    private array $modelPaths = [];

    /**
     * @var array<string>
     */
    private array $excludePatterns = [];

    /**
     * @var array<string>|null
     */
    private ?array $cachedFiles = null;

    /**
     * @param array<string> $controllerPaths
     * @param array<string> $modelPaths
     * @param array<string> $excludePatterns
     */
    public function __construct(
        array $controllerPaths = [],
        array $modelPaths = [],
        array $excludePatterns = [],
    ) {
        $this->controllerPaths = $controllerPaths;
        $this->modelPaths = $modelPaths;
        $this->excludePatterns = $excludePatterns;
    }

    /**
     * Scan directories for PHP files.
     *
     * @param array<string> $paths
     * @return array<string>
     */
    public function scan(array $paths): array
    {
        $files = [];

        foreach ($paths as $path) {
            if (is_file($path) && $this->isPhpFile($path)) {
                $files[] = $path;
                continue;
            }

            if (is_dir($path)) {
                $files = array_merge($files, $this->scanDirectory($path));
            }
        }

        // Remove duplicates and filter excluded
        $files = array_unique($files);
        $files = $this->filterExcluded($files);

        return array_values($files);
    }

    /**
     * Get relevant files that may contain OpenAPI metadata.
     *
     * @return array<string>
     */
    public function getRelevantFiles(): array
    {
        if ($this->cachedFiles !== null) {
            return $this->cachedFiles;
        }

        $files = [];

        // Scan controller paths
        $files = array_merge($files, $this->scan($this->controllerPaths));

        // Scan model paths
        $files = array_merge($files, $this->scan($this->modelPaths));

        // Remove duplicates
        $files = array_unique($files);

        // Cache for subsequent calls
        $this->cachedFiles = array_values($files);

        return $this->cachedFiles;
    }

    /**
     * Add controller path to scan.
     */
    public function addControllerPath(string $path): self
    {
        if (!in_array($path, $this->controllerPaths, true)) {
            $this->controllerPaths[] = $path;
            $this->cachedFiles = null; // Invalidate cache
        }

        return $this;
    }

    /**
     * Add model path to scan.
     */
    public function addModelPath(string $path): self
    {
        if (!in_array($path, $this->modelPaths, true)) {
            $this->modelPaths[] = $path;
            $this->cachedFiles = null; // Invalidate cache
        }

        return $this;
    }

    /**
     * Add exclude pattern.
     */
    public function addExcludePattern(string $pattern): self
    {
        if (!in_array($pattern, $this->excludePatterns, true)) {
            $this->excludePatterns[] = $pattern;
            $this->cachedFiles = null; // Invalidate cache
        }

        return $this;
    }

    /**
     * Get controller paths.
     *
     * @return array<string>
     */
    public function getControllerPaths(): array
    {
        return $this->controllerPaths;
    }

    /**
     * Get model paths.
     *
     * @return array<string>
     */
    public function getModelPaths(): array
    {
        return $this->modelPaths;
    }

    /**
     * Get exclude patterns.
     *
     * @return array<string>
     */
    public function getExcludePatterns(): array
    {
        return $this->excludePatterns;
    }

    /**
     * Clear cached files.
     */
    public function clearCache(): self
    {
        $this->cachedFiles = null;

        return $this;
    }

    /**
     * Scan a directory for PHP files.
     *
     * @return array<string>
     */
    private function scanDirectory(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $finder = new Finder();
        $finder->files()
            ->in($directory)
            ->name('*.php')
            ->ignoreVCS(true)
            ->ignoreUnreadableDirs()
            ->sortByName();

        $files = [];

        foreach ($finder as $file) {
            $files[] = $file->getRealPath();
        }

        return $files;
    }

    /**
     * Check if file is a PHP file.
     */
    private function isPhpFile(string $path): bool
    {
        return pathinfo($path, PATHINFO_EXTENSION) === 'php';
    }

    /**
     * Filter excluded files.
     *
     * @param array<string> $files
     * @return array<string>
     */
    private function filterExcluded(array $files): array
    {
        if (empty($this->excludePatterns)) {
            return $files;
        }

        return array_filter($files, function (string $file) {
            foreach ($this->excludePatterns as $pattern) {
                // Support both glob patterns and simple string matching
                if (
                    fnmatch($pattern, $file) ||
                    str_contains($file, $pattern)
                ) {
                    return false;
                }
            }

            return true;
        });
    }
}
