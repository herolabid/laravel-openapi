<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Parser;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use ReflectionNamedType;
use ReflectionUnionType;
use ReflectionIntersectionType;

/**
 * Resolves PHP types to OpenAPI schema types.
 */
class TypeResolver
{
    /**
     * Resolve a reflection type to OpenAPI schema.
     *
     * @return array<string, mixed>
     */
    public function resolve(
        ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|null $type,
    ): array {
        if ($type === null) {
            return ['type' => 'string'];
        }

        if ($type instanceof ReflectionNamedType) {
            return $this->resolveNamedType($type);
        }

        if ($type instanceof ReflectionUnionType) {
            return $this->resolveUnionType($type);
        }

        return ['type' => 'string'];
    }

    /**
     * Resolve a named type to OpenAPI schema.
     *
     * @return array<string, mixed>
     */
    private function resolveNamedType(ReflectionNamedType $type): array
    {
        $typeName = $type->getName();

        // Scalar types
        if ($this->isScalarType($typeName)) {
            return $this->resolveScalarType($typeName);
        }

        // Array type
        if ($typeName === 'array') {
            return [
                'type' => 'array',
                'items' => ['type' => 'string'], // Generic array
            ];
        }

        // Try to resolve as class
        if (class_exists($typeName)) {
            return $this->resolveClassType($typeName);
        }

        return ['type' => 'string'];
    }

    /**
     * Resolve a union type to OpenAPI schema.
     *
     * @return array<string, mixed>
     */
    private function resolveUnionType(ReflectionUnionType $type): array
    {
        $types = $type->getTypes();
        $nonNullTypes = array_filter(
            $types,
            fn($t) => !($t instanceof ReflectionNamedType && $t->getName() === 'null')
        );

        // If only one non-null type, use it
        if (count($nonNullTypes) === 1) {
            $schema = $this->resolveNamedType(array_values($nonNullTypes)[0]);
            $schema['nullable'] = true;
            return $schema;
        }

        // Multiple types - use oneOf
        return [
            'oneOf' => array_map(
                fn($t) => $this->resolve($t),
                $nonNullTypes
            ),
        ];
    }

    /**
     * Check if type name is a scalar type.
     */
    private function isScalarType(string $typeName): bool
    {
        return in_array($typeName, [
            'int', 'integer',
            'float', 'double',
            'string',
            'bool', 'boolean',
            'mixed',
        ], true);
    }

    /**
     * Resolve scalar type to OpenAPI schema.
     *
     * @return array<string, mixed>
     */
    private function resolveScalarType(string $typeName): array
    {
        return match ($typeName) {
            'int', 'integer' => ['type' => 'integer'],
            'float', 'double' => ['type' => 'number', 'format' => 'float'],
            'string' => ['type' => 'string'],
            'bool', 'boolean' => ['type' => 'boolean'],
            'mixed' => ['type' => 'string'],
            default => ['type' => 'string'],
        };
    }

    /**
     * Resolve class type to OpenAPI schema.
     *
     * @return array<string, mixed>
     */
    private function resolveClassType(string $className): array
    {
        // Eloquent Model
        if (is_subclass_of($className, Model::class)) {
            return [
                '$ref' => '#/components/schemas/' . class_basename($className),
            ];
        }

        // JSON Resource
        if (is_subclass_of($className, JsonResource::class)) {
            // Try to infer model from resource name
            $modelName = str_replace('Resource', '', class_basename($className));
            return [
                '$ref' => '#/components/schemas/' . $modelName,
            ];
        }

        // DateTime/Carbon
        if ($this->isDateTimeClass($className)) {
            return [
                'type' => 'string',
                'format' => 'date-time',
            ];
        }

        // Generic object
        return ['type' => 'object'];
    }

    /**
     * Check if class is a DateTime class.
     */
    private function isDateTimeClass(string $className): bool
    {
        return in_array($className, [
            'DateTime',
            'DateTimeImmutable',
            'Carbon\Carbon',
            'Carbon\CarbonImmutable',
            'Illuminate\Support\Carbon',
        ], true) || is_subclass_of($className, \DateTimeInterface::class);
    }

    /**
     * Resolve PHP doc type to OpenAPI schema.
     * This is a fallback for when reflection types are not available.
     *
     * @return array<string, mixed>
     */
    public function resolveFromDocBlock(string $docType): array
    {
        // Remove nullable prefix
        $docType = ltrim($docType, '?');

        // Handle array notation
        if (str_ends_with($docType, '[]')) {
            $itemType = substr($docType, 0, -2);
            return [
                'type' => 'array',
                'items' => $this->resolveFromDocBlock($itemType),
            ];
        }

        // Handle array<Type> notation
        if (preg_match('/^array<(.+)>$/', $docType, $matches)) {
            return [
                'type' => 'array',
                'items' => $this->resolveFromDocBlock($matches[1]),
            ];
        }

        // Scalar types
        if ($this->isScalarType($docType)) {
            return $this->resolveScalarType($docType);
        }

        // Try class
        if (class_exists($docType)) {
            return $this->resolveClassType($docType);
        }

        return ['type' => 'string'];
    }

    /**
     * Get OpenAPI format for a given type.
     */
    public function getFormat(string $typeName): ?string
    {
        return match ($typeName) {
            'float', 'double' => 'float',
            'DateTime', 'DateTimeImmutable', 'Carbon\Carbon' => 'date-time',
            default => null,
        };
    }
}
