<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Infrastructure\Cache;

/**
 * Track file dependencies for incremental cache invalidation.
 *
 * Allows invalidating only affected parts when a file changes.
 */
class DependencyTracker
{
    /**
     * @var array<string, array<string>>
     */
    private array $dependencies = [];

    /**
     * @var array<string, array<string>>
     */
    private array $reverseDependencies = [];

    /**
     * Track that a file depends on other files.
     *
     * @param string $file The dependent file
     * @param array<string> $dependencies Files it depends on
     */
    public function track(string $file, array $dependencies): void
    {
        $this->dependencies[$file] = $dependencies;

        // Build reverse map for efficient lookup
        foreach ($dependencies as $dependency) {
            if (!isset($this->reverseDependencies[$dependency])) {
                $this->reverseDependencies[$dependency] = [];
            }

            if (!in_array($file, $this->reverseDependencies[$dependency], true)) {
                $this->reverseDependencies[$dependency][] = $file;
            }
        }
    }

    /**
     * Get files that depend on the given file.
     *
     * @param string $file The file that changed
     * @return array<string>
     */
    public function getDependents(string $file): array
    {
        return $this->reverseDependencies[$file] ?? [];
    }

    /**
     * Get all affected files when a file changes.
     * Includes the file itself and all its dependents (recursively).
     *
     * @param string $file The changed file
     * @return array<string>
     */
    public function getAffectedFiles(string $file): array
    {
        $affected = [$file];
        $this->collectDependents($file, $affected);

        return array_unique($affected);
    }

    /**
     * Get all affected files for multiple changed files.
     *
     * @param array<string> $files
     * @return array<string>
     */
    public function getAffectedFilesForMany(array $files): array
    {
        $affected = [];

        foreach ($files as $file) {
            $affected = array_merge($affected, $this->getAffectedFiles($file));
        }

        return array_unique($affected);
    }

    /**
     * Recursively collect all dependents of a file.
     *
     * @param array<string> $collected
     */
    private function collectDependents(string $file, array &$collected): void
    {
        $dependents = $this->getDependents($file);

        foreach ($dependents as $dependent) {
            if (in_array($dependent, $collected, true)) {
                continue; // Avoid circular dependencies
            }

            $collected[] = $dependent;
            $this->collectDependents($dependent, $collected);
        }
    }

    /**
     * Get dependencies of a file.
     *
     * @return array<string>
     */
    public function getDependencies(string $file): array
    {
        return $this->dependencies[$file] ?? [];
    }

    /**
     * Check if a file has dependencies.
     */
    public function hasDependencies(string $file): bool
    {
        return isset($this->dependencies[$file]) && !empty($this->dependencies[$file]);
    }

    /**
     * Check if a file has dependents.
     */
    public function hasDependents(string $file): bool
    {
        return isset($this->reverseDependencies[$file]) && !empty($this->reverseDependencies[$file]);
    }

    /**
     * Clear all tracked dependencies.
     */
    public function clear(): void
    {
        $this->dependencies = [];
        $this->reverseDependencies = [];
    }

    /**
     * Remove tracking for a specific file.
     */
    public function remove(string $file): void
    {
        // Remove from dependencies
        if (isset($this->dependencies[$file])) {
            $dependencies = $this->dependencies[$file];

            foreach ($dependencies as $dependency) {
                if (isset($this->reverseDependencies[$dependency])) {
                    $this->reverseDependencies[$dependency] = array_filter(
                        $this->reverseDependencies[$dependency],
                        fn($f) => $f !== $file
                    );

                    if (empty($this->reverseDependencies[$dependency])) {
                        unset($this->reverseDependencies[$dependency]);
                    }
                }
            }

            unset($this->dependencies[$file]);
        }

        // Remove from reverse dependencies
        if (isset($this->reverseDependencies[$file])) {
            unset($this->reverseDependencies[$file]);
        }
    }

    /**
     * Get all tracked files.
     *
     * @return array<string>
     */
    public function getAllFiles(): array
    {
        return array_unique(array_merge(
            array_keys($this->dependencies),
            array_keys($this->reverseDependencies)
        ));
    }

    /**
     * Get dependency statistics.
     *
     * @return array{total_files: int, files_with_dependencies: int, files_with_dependents: int}
     */
    public function getStats(): array
    {
        return [
            'total_files' => count($this->getAllFiles()),
            'files_with_dependencies' => count($this->dependencies),
            'files_with_dependents' => count($this->reverseDependencies),
        ];
    }

    /**
     * Export dependencies for caching.
     *
     * @return array<string, array<string>>
     */
    public function export(): array
    {
        return $this->dependencies;
    }

    /**
     * Import dependencies from cache.
     *
     * @param array<string, array<string>> $dependencies
     */
    public function import(array $dependencies): void
    {
        $this->clear();

        foreach ($dependencies as $file => $deps) {
            $this->track($file, $deps);
        }
    }
}
