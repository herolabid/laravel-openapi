<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Exceptions;

/**
 * Exception thrown when attribute operations fail.
 */
class AttributeException extends OpenApiException
{
    /**
     * Create exception for invalid attribute value.
     */
    public static function invalidValue(
        string $attribute,
        string $property,
        mixed $value,
        string $expected
    ): static {
        $actualType = get_debug_type($value);

        return static::withContext(
            "Invalid value for {$attribute}::\${$property}. " .
            "Expected {$expected}, got {$actualType}",
            [
                'attribute' => $attribute,
                'property' => $property,
                'value' => $value,
                'expected' => $expected,
                'actual' => $actualType,
            ]
        );
    }

    /**
     * Create exception for missing required property.
     */
    public static function missingRequired(string $attribute, string $property): static
    {
        return static::withContext(
            "Missing required property '{$property}' in attribute '{$attribute}'",
            [
                'attribute' => $attribute,
                'property' => $property,
            ]
        );
    }
}
