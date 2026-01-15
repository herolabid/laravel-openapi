<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Tests\Support;

/**
 * Trait for OpenAPI spec assertions.
 */
trait HasOpenApiAssertions
{
    /**
     * Assert that the given array is a valid OpenAPI spec.
     *
     * @param array<string, mixed> $spec
     */
    protected function assertValidOpenApiSpec(array $spec): void
    {
        $this->assertArrayHasKey('openapi', $spec);
        $this->assertMatchesRegularExpression('/^3\.1\.\d+$/', $spec['openapi']);
        $this->assertArrayHasKey('info', $spec);
        $this->assertArrayHasKey('title', $spec['info']);
        $this->assertArrayHasKey('version', $spec['info']);
    }

    /**
     * Assert that spec has the given path.
     *
     * @param array<string, mixed> $spec
     */
    protected function assertHasPath(array $spec, string $path, string $method): void
    {
        $this->assertArrayHasKey('paths', $spec);
        $this->assertArrayHasKey($path, $spec['paths']);
        $this->assertArrayHasKey(strtolower($method), $spec['paths'][$path]);
    }

    /**
     * Assert that spec has the given schema.
     *
     * @param array<string, mixed> $spec
     */
    protected function assertHasSchema(array $spec, string $schemaName): void
    {
        $this->assertArrayHasKey('components', $spec);
        $this->assertArrayHasKey('schemas', $spec['components']);
        $this->assertArrayHasKey($schemaName, $spec['components']['schemas']);
    }

    /**
     * Assert that spec has the given security scheme.
     *
     * @param array<string, mixed> $spec
     */
    protected function assertHasSecurityScheme(array $spec, string $schemeName): void
    {
        $this->assertArrayHasKey('components', $spec);
        $this->assertArrayHasKey('securitySchemes', $spec['components']);
        $this->assertArrayHasKey($schemeName, $spec['components']['securitySchemes']);
    }

    /**
     * Assert that operation has the given parameter.
     *
     * @param array<string, mixed> $operation
     */
    protected function assertHasParameter(array $operation, string $name): void
    {
        $this->assertArrayHasKey('parameters', $operation);

        $parameterNames = array_column($operation['parameters'], 'name');
        $this->assertContains($name, $parameterNames);
    }
}
