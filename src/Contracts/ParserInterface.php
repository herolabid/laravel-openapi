<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Contracts;

/**
 * Interface for parsing PHP files to extract OpenAPI metadata.
 */
interface ParserInterface
{
    /**
     * Parse controllers to extract OpenAPI paths.
     *
     * @return array<string, mixed>
     */
    public function parseControllers(array $paths): array;

    /**
     * Parse models to extract OpenAPI schemas.
     *
     * @return array<string, mixed>
     */
    public function parseModels(array $paths): array;

    /**
     * Parse a single class to extract attributes.
     *
     * @return array<string, mixed>
     */
    public function parseClass(string $className): array;
}
