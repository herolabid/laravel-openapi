<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Builders;

use HerolabID\LaravelOpenApi\Contracts\BuilderInterface;

/**
 * Builder for OpenAPI parameter objects.
 */
class ParameterBuilder implements BuilderInterface
{
    /**
     * Build parameters from parsed data.
     *
     * @param array<array<string, mixed>> $data
     * @return array<array<string, mixed>>
     */
    public function build(array $data): array
    {
        if (empty($data)) {
            return [];
        }

        $parameters = [];

        foreach ($data as $parameter) {
            $parameters[] = $this->buildParameter($parameter);
        }

        return $parameters;
    }

    /**
     * Build a single parameter object.
     *
     * @param array<string, mixed> $parameter
     * @return array<string, mixed>
     */
    private function buildParameter(array $parameter): array
    {
        $built = [
            'name' => $parameter['name'],
            'in' => $parameter['in'] ?? 'query',
        ];

        // Description
        if (isset($parameter['description'])) {
            $built['description'] = $parameter['description'];
        }

        // Required
        if (isset($parameter['required']) && $parameter['required']) {
            $built['required'] = true;
        }

        // Deprecated
        if (isset($parameter['deprecated']) && $parameter['deprecated']) {
            $built['deprecated'] = true;
        }

        // Schema
        if (isset($parameter['schema'])) {
            $built['schema'] = $parameter['schema'];
        }

        // Example
        if (isset($parameter['example'])) {
            $built['example'] = $parameter['example'];
        }

        // Examples (multiple)
        if (isset($parameter['examples'])) {
            $built['examples'] = $parameter['examples'];
        }

        // Style
        if (isset($parameter['style'])) {
            $built['style'] = $parameter['style'];
        }

        // Explode
        if (isset($parameter['explode'])) {
            $built['explode'] = $parameter['explode'];
        }

        return $built;
    }

    /**
     * Group parameters by location.
     *
     * @param array<array<string, mixed>> $parameters
     * @return array<string, array<array<string, mixed>>>
     */
    public function groupByLocation(array $parameters): array
    {
        $grouped = [
            'path' => [],
            'query' => [],
            'header' => [],
            'cookie' => [],
        ];

        foreach ($parameters as $parameter) {
            $location = $parameter['in'] ?? 'query';
            $grouped[$location][] = $parameter;
        }

        return array_filter($grouped);
    }
}
