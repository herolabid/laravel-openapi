<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Exceptions;

use Exception;

/**
 * Base exception for all Laravel OpenAPI exceptions.
 */
class OpenApiException extends Exception
{
    /**
     * Additional context about the exception.
     *
     * @var array<string, mixed>
     */
    protected array $context = [];

    /**
     * Create a new OpenAPI exception.
     *
     * @param array<string, mixed> $context
     */
    public function __construct(
        string $message = '',
        array $context = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get exception context.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Create exception with context.
     *
     * @param array<string, mixed> $context
     */
    public static function withContext(string $message, array $context = []): static
    {
        return new static($message, $context);
    }
}
