<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Exceptions;

/**
 * Exception thrown when parsing attributes fails.
 */
class ParsingException extends OpenApiException
{
    /**
     * Create exception for invalid attribute.
     *
     * @param array<string, mixed> $context
     */
    public static function invalidAttribute(
        string $className,
        string $method,
        string $attribute,
        array $context = []
    ): static {
        $message = "Invalid attribute '{$attribute}' in {$className}::{$method}()";

        return static::withContext($message, array_merge([
            'class' => $className,
            'method' => $method,
            'attribute' => $attribute,
        ], $context));
    }

    /**
     * Create exception for missing required attribute.
     *
     * @param array<string, mixed> $context
     */
    public static function missingRequiredAttribute(
        string $className,
        string $method,
        string $attribute,
        string $suggestion = '',
        array $context = []
    ): static {
        $message = "Missing required attribute '{$attribute}' in {$className}::{$method}()";

        if ($suggestion) {
            $message .= "\n\nSuggestion:\n{$suggestion}";
        }

        return static::withContext($message, array_merge([
            'class' => $className,
            'method' => $method,
            'attribute' => $attribute,
        ], $context));
    }

    /**
     * Create exception for class not found.
     */
    public static function classNotFound(string $className): static
    {
        return static::withContext(
            "Class '{$className}' not found",
            ['class' => $className]
        );
    }
}
