<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Exceptions;

/**
 * Exception thrown when cache operations fail.
 */
class CacheException extends OpenApiException
{
    /**
     * Create exception for cache read failure.
     */
    public static function readFailed(string $key, string $reason = ''): static
    {
        $message = "Failed to read from cache: {$key}";

        if ($reason) {
            $message .= " - {$reason}";
        }

        return static::withContext($message, [
            'key' => $key,
            'reason' => $reason,
        ]);
    }

    /**
     * Create exception for cache write failure.
     */
    public static function writeFailed(string $key, string $reason = ''): static
    {
        $message = "Failed to write to cache: {$key}";

        if ($reason) {
            $message .= " - {$reason}";
        }

        return static::withContext($message, [
            'key' => $key,
            'reason' => $reason,
        ]);
    }

    /**
     * Create exception for cache driver not supported.
     */
    public static function driverNotSupported(string $driver): static
    {
        return static::withContext(
            "Cache driver '{$driver}' is not supported",
            ['driver' => $driver]
        );
    }
}
