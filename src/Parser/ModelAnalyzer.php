<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Parser;

use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use HerolabID\LaravelOpenApi\Attributes\Schema;
use HerolabID\LaravelOpenApi\Attributes\Property;

/**
 * Analyzer for Eloquent models to extract OpenAPI schemas.
 */
class ModelAnalyzer
{
    public function __construct(
        private readonly ReflectionHelper $reflectionHelper,
        private readonly TypeResolver $typeResolver,
    ) {}

    /**
     * Analyze a model class and extract schema.
     *
     * @return array<string, mixed>
     */
    public function analyze(string $modelClass): array
    {
        if (!class_exists($modelClass)) {
            return [];
        }

        if (!is_subclass_of($modelClass, Model::class)) {
            return [];
        }

        $reflection = new ReflectionClass($modelClass);
        $schemaName = class_basename($modelClass);

        // Check for Schema attribute
        $schemaAttributes = $this->reflectionHelper->getClassAttributes(
            $reflection,
            Schema::class
        );

        $schema = [
            'type' => 'object',
            'properties' => [],
        ];

        if (!empty($schemaAttributes)) {
            /** @var Schema $schemaAttr */
            $schemaAttr = $schemaAttributes[0];
            $schema = array_merge($schema, $schemaAttr->toArray());
        } else {
            // Auto-generate basic schema info
            $schema['title'] = $schemaName;
            $schema['description'] = $schemaName . ' model';
        }

        // Extract properties
        $properties = $this->extractProperties($reflection);
        if (!empty($properties)) {
            $schema['properties'] = array_merge(
                $schema['properties'] ?? [],
                $properties['properties']
            );

            if (!empty($properties['required'])) {
                $schema['required'] = array_unique(array_merge(
                    $schema['required'] ?? [],
                    $properties['required']
                ));
            }
        }

        return [$schemaName => $schema];
    }

    /**
     * Analyze multiple model classes.
     *
     * @param array<string> $modelClasses
     * @return array<string, mixed>
     */
    public function analyzeMany(array $modelClasses): array
    {
        $schemas = [];

        foreach ($modelClasses as $modelClass) {
            $schema = $this->analyze($modelClass);
            $schemas = array_merge($schemas, $schema);
        }

        return $schemas;
    }

    /**
     * Extract properties from model class.
     *
     * @return array{properties: array<string, mixed>, required: array<string>}
     */
    private function extractProperties(ReflectionClass $class): array
    {
        $properties = [];
        $required = [];

        $reflectionProperties = $this->reflectionHelper->getProperties($class);

        foreach ($reflectionProperties as $property) {
            // Get Property attributes
            $propertyAttributes = $this->reflectionHelper->getPropertyAttributes(
                $property,
                Property::class
            );

            if (!empty($propertyAttributes)) {
                /** @var Property $propertyAttr */
                $propertyAttr = $propertyAttributes[0];
                $properties[$property->getName()] = $propertyAttr->toArray();

                // Check if required (non-nullable)
                if (!$propertyAttr->nullable && $propertyAttr->default === null) {
                    $required[] = $property->getName();
                }
            } else {
                // Auto-infer from type hint
                $type = $property->getType();
                $schema = $this->typeResolver->resolve($type);

                if ($this->reflectionHelper->isNullable($type)) {
                    $schema['nullable'] = true;
                } else {
                    $required[] = $property->getName();
                }

                $properties[$property->getName()] = $schema;
            }
        }

        return [
            'properties' => $properties,
            'required' => $required,
        ];
    }

    /**
     * Get fillable properties from model.
     * This is used when no Property attributes are defined.
     *
     * @return array<string>
     */
    private function getFillableProperties(Model $model): array
    {
        return $model->getFillable();
    }

    /**
     * Get hidden properties from model.
     * These should not be included in the schema.
     *
     * @return array<string>
     */
    private function getHiddenProperties(Model $model): array
    {
        return $model->getHidden();
    }

    /**
     * Infer property type from database column type.
     * This is a fallback when no type hints are available.
     *
     * @return array<string, mixed>
     */
    private function inferTypeFromColumn(string $columnType): array
    {
        return match (true) {
            str_contains($columnType, 'int') => ['type' => 'integer'],
            str_contains($columnType, 'decimal') || str_contains($columnType, 'float') => [
                'type' => 'number',
                'format' => 'float',
            ],
            str_contains($columnType, 'bool') => ['type' => 'boolean'],
            str_contains($columnType, 'json') => ['type' => 'object'],
            str_contains($columnType, 'date') || str_contains($columnType, 'time') => [
                'type' => 'string',
                'format' => 'date-time',
            ],
            str_contains($columnType, 'text') => ['type' => 'string'],
            default => ['type' => 'string'],
        };
    }
}
