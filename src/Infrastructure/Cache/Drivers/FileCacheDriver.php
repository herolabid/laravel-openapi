<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Infrastructure\Cache\Drivers;

/**
 * File-based cache driver for OpenAPI specs.
 *
 * Simple implementation that stores cache in filesystem.
 */
class FileCacheDriver
{
    private string $cachePath;

    public function __construct(?string $cachePath = null)
    {
        $this->cachePath = $cachePath ?? storage_path('framework/cache/openapi');
    }

    /**
     * Get cached data.
     *
     * @return mixed
     */
    public function get(string $key): mixed
    {
        $file = $this->getCacheFile($key);

        if (!file_exists($file)) {
            return null;
        }

        // Check if expired
        $data = unserialize(file_get_contents($file));

        if (isset($data['expires_at']) && $data['expires_at'] < time()) {
            $this->forget($key);
            return null;
        }

        return $data['value'] ?? null;
    }

    /**
     * Store data in cache.
     */
    public function put(string $key, mixed $value, int $ttl): void
    {
        $this->ensureCacheDirectoryExists();

        $file = $this->getCacheFile($key);
        $data = [
            'value' => $value,
            'expires_at' => time() + $ttl,
        ];

        file_put_contents($file, serialize($data), LOCK_EX);
    }

    /**
     * Remove data from cache.
     */
    public function forget(string $key): void
    {
        $file = $this->getCacheFile($key);

        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * Check if key exists in cache.
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Clear all cache.
     */
    public function flush(): void
    {
        if (!is_dir($this->cachePath)) {
            return;
        }

        $files = glob($this->cachePath . '/*.cache');

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Get cache file path for key.
     */
    private function getCacheFile(string $key): string
    {
        $hash = md5($key);
        return $this->cachePath . '/' . $hash . '.cache';
    }

    /**
     * Ensure cache directory exists.
     */
    private function ensureCacheDirectoryExists(): void
    {
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }
}
