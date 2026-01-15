<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Builders;

use HerolabID\LaravelOpenApi\Contracts\BuilderInterface;

/**
 * Builder for OpenAPI schemas (components/schemas).
 */
class SchemaBuilder implements BuilderInterface
{
    /**
     * Build schemas from parsed model data.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function build(array $data): array
    {
        if (empty($data)) {
            return [];
        }

        $schemas = [];

        foreach ($data as $schemaName => $schema) {
            $schemas[$schemaName] = $this->buildSchema($schema);
        }

        return $schemas;
    }

    /**
     * Build a single schema object.
     *
     * @param array<string, mixed> $schema
     * @return array<string, mixed>
     */
    private function buildSchema(array $schema): array
    {
        $built = [];

        // Type
        if (isset($schema['type'])) {
            $built['type'] = $schema['type'];
        }

        // Title and description
        if (isset($schema['title'])) {
            $built['title'] = $schema['title'];
        }

        if (isset($schema['description'])) {
            $built['description'] = $schema['description'];
        }

        // Required fields
        if (isset($schema['required']) && !empty($schema['required'])) {
            $built['required'] = array_values(array_unique($schema['required']));
        }

        // Properties
        if (isset($schema['properties']) && !empty($schema['properties'])) {
            $built['properties'] = $this->buildProperties($schema['properties']);
        }

        // Additional properties
        if (isset($schema['additionalProperties'])) {
            $built['additionalProperties'] = $schema['additionalProperties'];
        }

        // Items (for arrays)
        if (isset($schema['items'])) {
            $built['items'] = $schema['items'];
        }

        // Example
        if (isset($schema['example'])) {
            $built['example'] = $schema['example'];
        }

        // Enum
        if (isset($schema['enum'])) {
            $built['enum'] = $schema['enum'];
        }

        return $built;
    }

    /**
     * Build properties object.
     *
     * @param array<string, mixed> $properties
     * @return array<string, mixed>
     */
    private function buildProperties(array $properties): array
    {
        $built = [];

        foreach ($properties as $name => $property) {
            $built[$name] = $this->buildProperty($property);
        }

        return $built;
    }

    /**
     * Build a single property.
     *
     * @param array<string, mixed> $property
     * @return array<string, mixed>
     */
    private function buildProperty(array $property): array
    {
        $built = [];

        // Type
        if (isset($property['type'])) {
            $built['type'] = $property['type'];
        }

        // Format
        if (isset($property['format'])) {
            $built['format'] = $property['format'];
        }

        // Description
        if (isset($property['description'])) {
            $built['description'] = $property['description'];
        }

        // Example
        if (isset($property['example'])) {
            $built['example'] = $property['example'];
        }

        // Default
        if (isset($property['default'])) {
            $built['default'] = $property['default'];
        }

        // Nullable
        if (isset($property['nullable']) && $property['nullable']) {
            $built['nullable'] = true;
        }

        // Read/Write only
        if (isset($property['readOnly']) && $property['readOnly']) {
            $built['readOnly'] = true;
        }

        if (isset($property['writeOnly']) && $property['writeOnly']) {
            $built['writeOnly'] = true;
        }

        // Enum
        if (isset($property['enum'])) {
            $built['enum'] = $property['enum'];
        }

        // Validation constraints
        if (isset($property['minimum'])) {
            $built['minimum'] = $property['minimum'];
        }

        if (isset($property['maximum'])) {
            $built['maximum'] = $property['maximum'];
        }

        if (isset($property['minLength'])) {
            $built['minLength'] = $property['minLength'];
        }

        if (isset($property['maxLength'])) {
            $built['maxLength'] = $property['maxLength'];
        }

        if (isset($property['pattern'])) {
            $built['pattern'] = $property['pattern'];
        }

        // Items (for arrays)
        if (isset($property['items'])) {
            $built['items'] = $property['items'];
        }

        // Reference
        if (isset($property['$ref'])) {
            return ['$ref' => $property['$ref']];
        }

        return $built;
    }

    /**
     * Merge multiple schema collections.
     *
     * @param array<array<string, mixed>> $schemaCollections
     * @return array<string, mixed>
     */
    public function merge(array $schemaCollections): array
    {
        $merged = [];

        foreach ($schemaCollections as $schemas) {
            $merged = array_merge($merged, $schemas);
        }

        return $merged;
    }
}
