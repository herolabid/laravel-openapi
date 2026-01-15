<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Infrastructure\Cache;

use HerolabID\LaravelOpenApi\Contracts\CacheInterface;
use HerolabID\LaravelOpenApi\Exceptions\CacheException;

/**
 * Cache manager with smart invalidation.
 *
 * Implements intelligent caching with file change detection:
 * - Calculates hash of all source files
 * - Only regenerates when files change
 * - Supports multiple cache drivers
 */
class CacheManager implements CacheInterface
{
    private string $cacheKey = 'openapi:spec';

    public function __construct(
        private readonly FileHasher $hasher,
        private readonly DependencyTracker $tracker,
        private readonly string $driver = 'file',
        private readonly int $ttl = 3600,
    ) {}

    /**
     * Get cached OpenAPI spec.
     *
     * @return array<string, mixed>|null
     */
    public function get(string $key): ?array
    {
        $hash = $this->hasher->calculate();
        $cacheKey = $this->buildCacheKey($key, $hash);

        try {
            $cached = $this->getFromStore($cacheKey);

            if ($cached === null) {
                return null;
            }

            // Verify hash matches
            if (isset($cached['_hash']) && $cached['_hash'] === $hash) {
                unset($cached['_hash']);
                return $cached;
            }

            return null;
        } catch (\Exception $e) {
            throw CacheException::readFailed($key, $e->getMessage());
        }
    }

    /**
     * Store OpenAPI spec in cache.
     *
     * @param array<string, mixed> $data
     */
    public function put(string $key, array $data, int $ttl): void
    {
        $hash = $this->hasher->calculate();
        $cacheKey = $this->buildCacheKey($key, $hash);

        // Add hash to data
        $data['_hash'] = $hash;

        try {
            $this->putToStore($cacheKey, $data, $ttl);

            // Store current hash separately for quick checking
            $this->putToStore('openapi:current_hash', $hash, $ttl);
        } catch (\Exception $e) {
            throw CacheException::writeFailed($key, $e->getMessage());
        }
    }

    /**
     * Remove spec from cache.
     */
    public function forget(string $key): void
    {
        // Get current hash
        $currentHash = $this->getFromStore('openapi:current_hash');

        if ($currentHash !== null) {
            $cacheKey = $this->buildCacheKey($key, $currentHash);
            $this->forgetFromStore($cacheKey);
        }

        // Also forget hash
        $this->forgetFromStore('openapi:current_hash');
    }

    /**
     * Check if spec exists in cache.
     */
    public function has(string $key): bool
    {
        $hash = $this->hasher->calculate();
        $cacheKey = $this->buildCacheKey($key, $hash);

        return $this->hasInStore($cacheKey);
    }

    /**
     * Check if cache needs regeneration.
     */
    public function needsRegeneration(): bool
    {
        // Get cached hash
        $cachedHash = $this->getFromStore('openapi:current_hash');

        if ($cachedHash === null) {
            return true;
        }

        // Calculate current hash
        $currentHash = $this->hasher->calculate();

        // Compare hashes
        return $cachedHash !== $currentHash;
    }

    /**
     * Build cache key with hash.
     */
    private function buildCacheKey(string $key, string $hash): string
    {
        return "{$key}:{$hash}";
    }

    /**
     * Get data from cache store.
     *
     * @return mixed
     */
    private function getFromStore(string $key): mixed
    {
        return cache()->store($this->driver)->get($key);
    }

    /**
     * Put data to cache store.
     */
    private function putToStore(string $key, mixed $data, int $ttl): void
    {
        cache()->store($this->driver)->put($key, $data, $ttl);
    }

    /**
     * Forget data from cache store.
     */
    private function forgetFromStore(string $key): void
    {
        cache()->store($this->driver)->forget($key);
    }

    /**
     * Check if key exists in cache store.
     */
    private function hasInStore(string $key): bool
    {
        return cache()->store($this->driver)->has($key);
    }

    /**
     * Get cache statistics.
     *
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        $cachedHash = $this->getFromStore('openapi:current_hash');
        $currentHash = $this->hasher->calculate();

        return [
            'driver' => $this->driver,
            'ttl' => $this->ttl,
            'has_cache' => $cachedHash !== null,
            'cached_hash' => $cachedHash,
            'current_hash' => $currentHash,
            'needs_regeneration' => $this->needsRegeneration(),
            'tracked_files' => count($this->hasher->getTrackedFiles()),
        ];
    }

    /**
     * Clear all OpenAPI cache.
     */
    public function clear(): void
    {
        $this->forget($this->cacheKey);
    }
}
