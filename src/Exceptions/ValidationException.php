<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Exceptions;

/**
 * Exception thrown when OpenAPI spec validation fails.
 */
class ValidationException extends OpenApiException
{
    /**
     * Create exception for invalid spec.
     *
     * @param array<string> $errors
     */
    public static function invalidSpec(array $errors): static
    {
        $message = "OpenAPI specification validation failed:\n";
        $message .= implode("\n", array_map(fn($e) => "  - {$e}", $errors));

        return static::withContext($message, ['errors' => $errors]);
    }

    /**
     * Create exception for missing required field.
     */
    public static function missingRequiredField(string $field, string $path = ''): static
    {
        $message = "Missing required field: {$field}";

        if ($path) {
            $message .= " at path: {$path}";
        }

        return static::withContext($message, [
            'field' => $field,
            'path' => $path,
        ]);
    }

    /**
     * Create exception for invalid reference.
     */
    public static function invalidReference(string $ref): static
    {
        return static::withContext(
            "Invalid \$ref: {$ref}",
            ['ref' => $ref]
        );
    }
}
