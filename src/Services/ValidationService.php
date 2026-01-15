<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Services;

use HerolabID\LaravelOpenApi\Exceptions\ValidationException;

/**
 * Service for validating OpenAPI specifications.
 */
class ValidationService
{
    /**
     * @var array<string>
     */
    private array $errors = [];

    /**
     * @var array<string>
     */
    private array $warnings = [];

    /**
     * Validate OpenAPI specification.
     *
     * @param array<string, mixed> $spec
     * @throws ValidationException
     */
    public function validate(array $spec): void
    {
        $this->errors = [];
        $this->warnings = [];

        $this->validateStructure($spec);
        $this->validateInfo($spec);
        $this->validatePaths($spec);
        $this->validateComponents($spec);

        if (!empty($this->errors)) {
            throw ValidationException::invalidSpec($this->errors);
        }
    }

    /**
     * Validate basic structure.
     *
     * @param array<string, mixed> $spec
     */
    private function validateStructure(array $spec): void
    {
        // OpenAPI version
        if (!isset($spec['openapi'])) {
            $this->errors[] = 'Missing required field: openapi';
            return;
        }

        if (!preg_match('/^3\.(0|1)\.\d+$/', $spec['openapi'])) {
            $this->errors[] = 'Invalid OpenAPI version: ' . $spec['openapi'];
        }

        // Info is required
        if (!isset($spec['info'])) {
            $this->errors[] = 'Missing required field: info';
        }
    }

    /**
     * Validate info object.
     *
     * @param array<string, mixed> $spec
     */
    private function validateInfo(array $spec): void
    {
        if (!isset($spec['info'])) {
            return;
        }

        $info = $spec['info'];

        // Title is required
        if (!isset($info['title']) || empty($info['title'])) {
            $this->errors[] = 'Missing required field: info.title';
        }

        // Version is required
        if (!isset($info['version']) || empty($info['version'])) {
            $this->errors[] = 'Missing required field: info.version';
        }
    }

    /**
     * Validate paths object.
     *
     * @param array<string, mixed> $spec
     */
    private function validatePaths(array $spec): void
    {
        if (!isset($spec['paths'])) {
            $this->warnings[] = 'No paths defined';
            return;
        }

        foreach ($spec['paths'] as $path => $pathItem) {
            $this->validatePath($path, $pathItem);
        }
    }

    /**
     * Validate a single path.
     *
     * @param array<string, mixed> $pathItem
     */
    private function validatePath(string $path, array $pathItem): void
    {
        // Path must start with /
        if (!str_starts_with($path, '/')) {
            $this->errors[] = "Path must start with /: {$path}";
        }

        // Validate operations
        $validMethods = ['get', 'post', 'put', 'patch', 'delete', 'options', 'head', 'trace'];

        foreach ($pathItem as $method => $operation) {
            if (!in_array($method, $validMethods, true)) {
                continue;
            }

            $this->validateOperation($path, $method, $operation);
        }
    }

    /**
     * Validate an operation.
     *
     * @param array<string, mixed> $operation
     */
    private function validateOperation(string $path, string $method, array $operation): void
    {
        // Responses is required
        if (!isset($operation['responses']) || empty($operation['responses'])) {
            $this->errors[] = "Missing responses for {$method} {$path}";
            return;
        }

        // Validate responses
        foreach ($operation['responses'] as $statusCode => $response) {
            $this->validateResponse($path, $method, $statusCode, $response);
        }
    }

    /**
     * Validate a response.
     *
     * @param array<string, mixed> $response
     */
    private function validateResponse(
        string $path,
        string $method,
        string $statusCode,
        array $response
    ): void {
        // Description is required
        if (!isset($response['description']) || empty($response['description'])) {
            $this->errors[] = "Missing response description for {$method} {$path} ({$statusCode})";
        }

        // Validate status code
        if (!is_numeric($statusCode) || (int) $statusCode < 100 || (int) $statusCode > 599) {
            $this->errors[] = "Invalid status code: {$statusCode} for {$method} {$path}";
        }
    }

    /**
     * Validate components object.
     *
     * @param array<string, mixed> $spec
     */
    private function validateComponents(array $spec): void
    {
        if (!isset($spec['components'])) {
            return;
        }

        $components = $spec['components'];

        // Validate schemas
        if (isset($components['schemas'])) {
            foreach ($components['schemas'] as $schemaName => $schema) {
                $this->validateSchema($schemaName, $schema);
            }
        }

        // Validate security schemes
        if (isset($components['securitySchemes'])) {
            foreach ($components['securitySchemes'] as $schemeName => $scheme) {
                $this->validateSecurityScheme($schemeName, $scheme);
            }
        }
    }

    /**
     * Validate a schema.
     *
     * @param array<string, mixed> $schema
     */
    private function validateSchema(string $name, array $schema): void
    {
        // If it's a reference, validate the reference
        if (isset($schema['$ref'])) {
            $this->validateReference($schema['$ref']);
            return;
        }

        // Type or oneOf/anyOf/allOf is recommended
        if (!isset($schema['type']) &&
            !isset($schema['oneOf']) &&
            !isset($schema['anyOf']) &&
            !isset($schema['allOf'])) {
            $this->warnings[] = "Schema '{$name}' has no type definition";
        }

        // If type is object, should have properties
        if (isset($schema['type']) && $schema['type'] === 'object') {
            if (!isset($schema['properties']) || empty($schema['properties'])) {
                $this->warnings[] = "Object schema '{$name}' has no properties";
            }
        }

        // If type is array, should have items
        if (isset($schema['type']) && $schema['type'] === 'array') {
            if (!isset($schema['items'])) {
                $this->errors[] = "Array schema '{$name}' must have items definition";
            }
        }
    }

    /**
     * Validate a reference.
     */
    private function validateReference(string $ref): void
    {
        // Reference must start with #/
        if (!str_starts_with($ref, '#/')) {
            $this->errors[] = "Invalid reference format: {$ref}";
        }
    }

    /**
     * Validate a security scheme.
     *
     * @param array<string, mixed> $scheme
     */
    private function validateSecurityScheme(string $name, array $scheme): void
    {
        // Type is required
        if (!isset($scheme['type'])) {
            $this->errors[] = "Missing type for security scheme '{$name}'";
            return;
        }

        $validTypes = ['apiKey', 'http', 'oauth2', 'openIdConnect'];
        if (!in_array($scheme['type'], $validTypes, true)) {
            $this->errors[] = "Invalid security scheme type: {$scheme['type']}";
        }

        // Validate based on type
        match ($scheme['type']) {
            'http' => $this->validateHttpScheme($name, $scheme),
            'apiKey' => $this->validateApiKeyScheme($name, $scheme),
            'oauth2' => $this->validateOAuth2Scheme($name, $scheme),
            'openIdConnect' => $this->validateOpenIdConnectScheme($name, $scheme),
            default => null,
        };
    }

    /**
     * Validate HTTP security scheme.
     *
     * @param array<string, mixed> $scheme
     */
    private function validateHttpScheme(string $name, array $scheme): void
    {
        if (!isset($scheme['scheme'])) {
            $this->errors[] = "Missing scheme for HTTP security scheme '{$name}'";
        }
    }

    /**
     * Validate API Key security scheme.
     *
     * @param array<string, mixed> $scheme
     */
    private function validateApiKeyScheme(string $name, array $scheme): void
    {
        if (!isset($scheme['name'])) {
            $this->errors[] = "Missing name for API Key security scheme '{$name}'";
        }

        if (!isset($scheme['in'])) {
            $this->errors[] = "Missing 'in' for API Key security scheme '{$name}'";
        }
    }

    /**
     * Validate OAuth2 security scheme.
     *
     * @param array<string, mixed> $scheme
     */
    private function validateOAuth2Scheme(string $name, array $scheme): void
    {
        if (!isset($scheme['flows']) || empty($scheme['flows'])) {
            $this->errors[] = "Missing flows for OAuth2 security scheme '{$name}'";
        }
    }

    /**
     * Validate OpenID Connect security scheme.
     *
     * @param array<string, mixed> $scheme
     */
    private function validateOpenIdConnectScheme(string $name, array $scheme): void
    {
        if (!isset($scheme['openIdConnectUrl'])) {
            $this->errors[] = "Missing openIdConnectUrl for OpenID Connect scheme '{$name}'";
        }
    }

    /**
     * Get validation errors.
     *
     * @return array<string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get validation warnings.
     *
     * @return array<string>
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Check if specification is valid.
     */
    public function isValid(array $spec): bool
    {
        try {
            $this->validate($spec);
            return true;
        } catch (ValidationException) {
            return false;
        }
    }
}
