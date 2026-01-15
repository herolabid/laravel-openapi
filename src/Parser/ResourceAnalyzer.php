<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Parser;

use ReflectionClass;
use ReflectionMethod;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Analyze Laravel API Resource classes to extract response schemas.
 *
 * Converts Laravel API Resources into OpenAPI response schemas.
 */
class ResourceAnalyzer
{
    public function __construct(
        private readonly TypeResolver $typeResolver,
    ) {}

    /**
     * Analyze a Resource class and extract schema.
     *
     * @param class-string<JsonResource> $resourceClass
     * @return array<string, mixed>
     */
    public function analyze(string $resourceClass): array
    {
        if (!class_exists($resourceClass)) {
            return [];
        }

        if (!is_subclass_of($resourceClass, JsonResource::class)) {
            return [];
        }

        try {
            $reflection = new ReflectionClass($resourceClass);

            // Check for toArray method
            if (!$reflection->hasMethod('toArray')) {
                return [];
            }

            $method = $reflection->getMethod('toArray');
            $schema = $this->analyzeToArrayMethod($method, $resourceClass);

            if (empty($schema)) {
                return [];
            }

            return $schema;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Analyze multiple Resource classes.
     *
     * @param array<class-string<JsonResource>> $resourceClasses
     * @return array<string, mixed>
     */
    public function analyzeMany(array $resourceClasses): array
    {
        $schemas = [];

        foreach ($resourceClasses as $resourceClass) {
            $schema = $this->analyze($resourceClass);

            if (!empty($schema)) {
                $name = $this->getSchemaName($resourceClass);
                $schemas[$name] = $schema;
            }
        }

        return $schemas;
    }

    /**
     * Analyze toArray method to extract schema structure.
     *
     * @return array<string, mixed>
     */
    private function analyzeToArrayMethod(ReflectionMethod $method, string $resourceClass): array
    {
        // Try to parse return type from docblock
        $docComment = $method->getDocComment();

        if ($docComment !== false) {
            $schema = $this->parseDocComment($docComment);

            if (!empty($schema)) {
                return $schema;
            }
        }

        // Try to infer from method code (basic analysis)
        $schema = $this->inferFromMethodCode($method, $resourceClass);

        if (!empty($schema)) {
            return $schema;
        }

        // Fallback: return generic object schema
        return [
            'type' => 'object',
            'description' => 'Response resource for ' . class_basename($resourceClass),
            'properties' => [],
        ];
    }

    /**
     * Parse schema from docblock.
     *
     * @return array<string, mixed>
     */
    private function parseDocComment(string $docComment): array
    {
        // Extract @return array annotation
        if (preg_match('/@return\s+array<(.+)>/', $docComment, $matches)) {
            // Simple array structure detected
            return [
                'type' => 'object',
                'additionalProperties' => true,
            ];
        }

        // Extract individual @property annotations
        if (preg_match_all('/@property(?:-read)?\s+(\S+)\s+\$(\w+)/', $docComment, $matches, PREG_SET_ORDER)) {
            $properties = [];

            foreach ($matches as $match) {
                $type = $match[1];
                $name = $match[2];

                $properties[$name] = $this->typeResolver->resolve($type);
            }

            if (!empty($properties)) {
                return [
                    'type' => 'object',
                    'properties' => $properties,
                ];
            }
        }

        return [];
    }

    /**
     * Infer schema from method code (basic static analysis).
     *
     * @return array<string, mixed>
     */
    private function inferFromMethodCode(ReflectionMethod $method, string $resourceClass): array
    {
        try {
            $fileName = $method->getFileName();

            if ($fileName === false) {
                return [];
            }

            $startLine = $method->getStartLine();
            $endLine = $method->getEndLine();

            if ($startLine === false || $endLine === false) {
                return [];
            }

            $source = file($fileName);

            if ($source === false) {
                return [];
            }

            $methodCode = implode('', array_slice($source, $startLine - 1, $endLine - $startLine + 1));

            // Extract return array structure
            $properties = $this->extractArrayKeysFromCode($methodCode);

            if (!empty($properties)) {
                return [
                    'type' => 'object',
                    'properties' => $properties,
                    'description' => 'Response resource for ' . class_basename($resourceClass),
                ];
            }
        } catch (\Exception $e) {
            // Silently fail
        }

        return [];
    }

    /**
     * Extract array keys from code (simple pattern matching).
     *
     * @return array<string, mixed>
     */
    private function extractArrayKeysFromCode(string $code): array
    {
        $properties = [];

        // Match: 'key' => value or "key" => value
        if (preg_match_all("/['\"](\w+)['\"]\s*=>/", $code, $matches)) {
            foreach ($matches[1] as $key) {
                // Default to string type for now
                $properties[$key] = [
                    'type' => 'string',
                    'description' => ucfirst(str_replace('_', ' ', $key)),
                ];
            }
        }

        return $properties;
    }

    /**
     * Check if resource is a collection.
     *
     * @param class-string $resourceClass
     */
    public function isCollection(string $resourceClass): bool
    {
        return is_subclass_of($resourceClass, ResourceCollection::class);
    }

    /**
     * Build collection schema wrapper.
     *
     * @param array<string, mixed> $itemSchema
     * @return array<string, mixed>
     */
    public function buildCollectionSchema(array $itemSchema): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'data' => [
                    'type' => 'array',
                    'items' => $itemSchema,
                ],
                'links' => [
                    'type' => 'object',
                    'properties' => [
                        'first' => ['type' => 'string', 'format' => 'uri'],
                        'last' => ['type' => 'string', 'format' => 'uri'],
                        'prev' => ['type' => 'string', 'format' => 'uri', 'nullable' => true],
                        'next' => ['type' => 'string', 'format' => 'uri', 'nullable' => true],
                    ],
                ],
                'meta' => [
                    'type' => 'object',
                    'properties' => [
                        'current_page' => ['type' => 'integer'],
                        'from' => ['type' => 'integer', 'nullable' => true],
                        'last_page' => ['type' => 'integer'],
                        'per_page' => ['type' => 'integer'],
                        'to' => ['type' => 'integer', 'nullable' => true],
                        'total' => ['type' => 'integer'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get schema name from Resource class name.
     */
    private function getSchemaName(string $resourceClass): string
    {
        $baseName = class_basename($resourceClass);

        // Remove "Resource" suffix if exists
        if (str_ends_with($baseName, 'Resource')) {
            $baseName = substr($baseName, 0, -8);
        }

        // Remove "Collection" suffix if exists
        if (str_ends_with($baseName, 'Collection')) {
            $baseName = substr($baseName, 0, -10);
        }

        return $baseName . 'Resource';
    }

    /**
     * Analyze and return schema with collection wrapper if needed.
     *
     * @param class-string<JsonResource> $resourceClass
     * @return array<string, mixed>
     */
    public function analyzeWithCollection(string $resourceClass): array
    {
        $schema = $this->analyze($resourceClass);

        if (empty($schema)) {
            return [];
        }

        // Wrap in collection structure if it's a ResourceCollection
        if ($this->isCollection($resourceClass)) {
            return $this->buildCollectionSchema($schema);
        }

        return $schema;
    }
}
