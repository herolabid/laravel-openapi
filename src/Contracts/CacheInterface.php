<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Contracts;

/**
 * Interface for caching OpenAPI specifications.
 */
interface CacheInterface
{
    /**
     * Get cached OpenAPI spec.
     *
     * @return array<string, mixed>|null
     */
    public function get(string $key): ?array;

    /**
     * Store OpenAPI spec in cache.
     *
     * @param array<string, mixed> $data
     */
    public function put(string $key, array $data, int $ttl): void;

    /**
     * Remove spec from cache.
     */
    public function forget(string $key): void;

    /**
     * Check if spec exists in cache.
     */
    public function has(string $key): bool;

    /**
     * Check if cache needs regeneration.
     */
    public function needsRegeneration(): bool;
}
