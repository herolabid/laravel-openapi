<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Attributes;

use Attribute;

/**
 * Schema attribute for defining data models.
 *
 * Can be used on classes (models) or as a reference.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PARAMETER)]
class Schema
{
    /**
     * @param string|null $title Schema title
     * @param string|null $description Schema description
     * @param string|null $type Schema type (object, array, string, etc)
     * @param array<string> $required List of required properties
     * @param array<string, mixed> $properties Schema properties
     * @param string|class-string|null $ref Reference to another schema
     * @param array<string, mixed>|Schema|null $items Schema for array items
     * @param mixed $example Example value
     * @param array<string, mixed> $additionalProperties Additional properties configuration
     */
    public function __construct(
        public readonly ?string $title = null,
        public readonly ?string $description = null,
        public readonly ?string $type = null,
        public readonly array $required = [],
        public readonly array $properties = [],
        public readonly string|null $ref = null,
        public readonly array|Schema|null $items = null,
        public readonly mixed $example = null,
        public readonly array $additionalProperties = [],
    ) {}

    /**
     * Convert attribute to OpenAPI schema array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        // If it's a reference, return just the $ref
        if ($this->ref !== null) {
            $ref = $this->ref;

            // If ref is a class name, convert to schema reference
            if (class_exists($ref)) {
                $ref = '#/components/schemas/' . class_basename($ref);
            }

            return ['$ref' => $ref];
        }

        $schema = [];

        if ($this->title) {
            $schema['title'] = $this->title;
        }

        if ($this->description) {
            $schema['description'] = $this->description;
        }

        if ($this->type) {
            $schema['type'] = $this->type;
        }

        if (!empty($this->required)) {
            $schema['required'] = $this->required;
        }

        if (!empty($this->properties)) {
            $schema['properties'] = $this->properties;
        }

        if ($this->items !== null) {
            if ($this->items instanceof self) {
                $schema['items'] = $this->items->toArray();
            } else {
                $schema['items'] = $this->items;
            }
        }

        if ($this->example !== null) {
            $schema['example'] = $this->example;
        }

        if (!empty($this->additionalProperties)) {
            $schema['additionalProperties'] = $this->additionalProperties;
        }

        return $schema;
    }

    /**
     * Create a reference to a model schema.
     *
     * @param class-string $modelClass
     */
    public static function ref(string $modelClass): self
    {
        return new self(ref: $modelClass);
    }

    /**
     * Create an array schema.
     */
    public static function array(Schema|array $items, ?string $description = null): self
    {
        return new self(
            type: 'array',
            description: $description,
            items: $items,
        );
    }

    /**
     * Create an object schema.
     *
     * @param array<string, mixed> $properties
     * @param array<string> $required
     */
    public static function object(
        array $properties,
        array $required = [],
        ?string $description = null,
    ): self {
        return new self(
            type: 'object',
            description: $description,
            properties: $properties,
            required: $required,
        );
    }
}
