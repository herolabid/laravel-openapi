<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Services;

use HerolabID\LaravelOpenApi\Contracts\CacheInterface;

/**
 * Service for managing OpenAPI specification cache.
 */
class CacheInvalidationService
{
    public function __construct(
        private readonly CacheInterface $cache,
    ) {}

    /**
     * Clear all OpenAPI cache.
     */
    public function clearAll(): void
    {
        $this->cache->forget('openapi:spec');
    }

    /**
     * Clear cache for specific key.
     */
    public function clear(string $key): void
    {
        $this->cache->forget($key);
    }

    /**
     * Check if cache needs regeneration.
     */
    public function needsRegeneration(): bool
    {
        return $this->cache->needsRegeneration();
    }

    /**
     * Warm up the cache by generating spec.
     */
    public function warmUp(callable $generator): void
    {
        if ($this->cache->has('openapi:spec') && !$this->needsRegeneration()) {
            return;
        }

        $spec = $generator();
        $this->cache->put('openapi:spec', $spec, config('openapi.cache.ttl', 3600));
    }

    /**
     * Get cache statistics.
     *
     * @return array{has_cache: bool, needs_regeneration: bool}
     */
    public function getStats(): array
    {
        return [
            'has_cache' => $this->cache->has('openapi:spec'),
            'needs_regeneration' => $this->needsRegeneration(),
        ];
    }
}
