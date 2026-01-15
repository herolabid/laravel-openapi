<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Attributes;

use Attribute;

/**
 * Property attribute for schema properties.
 *
 * Used on class properties to define OpenAPI schema properties.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Property
{
    /**
     * @param string $type Property type (string, integer, number, boolean, array, object)
     * @param string|null $format Property format (date, date-time, email, uuid, etc)
     * @param string|null $description Property description
     * @param mixed $example Example value
     * @param mixed $default Default value
     * @param bool $nullable Whether the property can be null
     * @param bool $readOnly Whether the property is read-only
     * @param bool $writeOnly Whether the property is write-only
     * @param array<mixed>|null $enum Enumeration of possible values
     * @param int|float|null $minimum Minimum value (for numbers)
     * @param int|float|null $maximum Maximum value (for numbers)
     * @param int|null $minLength Minimum length (for strings)
     * @param int|null $maxLength Maximum length (for strings)
     * @param string|null $pattern Regex pattern (for strings)
     * @param array<string, mixed>|Schema|null $items Items schema (for arrays)
     */
    public function __construct(
        public readonly string $type,
        public readonly ?string $format = null,
        public readonly ?string $description = null,
        public readonly mixed $example = null,
        public readonly mixed $default = null,
        public readonly bool $nullable = false,
        public readonly bool $readOnly = false,
        public readonly bool $writeOnly = false,
        public readonly ?array $enum = null,
        public readonly int|float|null $minimum = null,
        public readonly int|float|null $maximum = null,
        public readonly ?int $minLength = null,
        public readonly ?int $maxLength = null,
        public readonly ?string $pattern = null,
        public readonly array|Schema|null $items = null,
    ) {}

    /**
     * Convert attribute to OpenAPI property array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $property = [
            'type' => $this->type,
        ];

        if ($this->format) {
            $property['format'] = $this->format;
        }

        if ($this->description) {
            $property['description'] = $this->description;
        }

        if ($this->example !== null) {
            $property['example'] = $this->example;
        }

        if ($this->default !== null) {
            $property['default'] = $this->default;
        }

        if ($this->nullable) {
            $property['nullable'] = true;
        }

        if ($this->readOnly) {
            $property['readOnly'] = true;
        }

        if ($this->writeOnly) {
            $property['writeOnly'] = true;
        }

        if ($this->enum !== null) {
            $property['enum'] = $this->enum;
        }

        if ($this->minimum !== null) {
            $property['minimum'] = $this->minimum;
        }

        if ($this->maximum !== null) {
            $property['maximum'] = $this->maximum;
        }

        if ($this->minLength !== null) {
            $property['minLength'] = $this->minLength;
        }

        if ($this->maxLength !== null) {
            $property['maxLength'] = $this->maxLength;
        }

        if ($this->pattern) {
            $property['pattern'] = $this->pattern;
        }

        if ($this->items !== null) {
            if ($this->items instanceof Schema) {
                $property['items'] = $this->items->toArray();
            } else {
                $property['items'] = $this->items;
            }
        }

        return $property;
    }
}
