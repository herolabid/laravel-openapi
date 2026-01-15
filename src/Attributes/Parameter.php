<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Attributes;

use Attribute;

/**
 * Parameter attribute for query, path, header, or cookie parameters.
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Parameter
{
    /**
     * @param string $name Parameter name
     * @param string $in Location: 'query', 'path', 'header', 'cookie'
     * @param string|null $description Parameter description
     * @param bool $required Whether the parameter is required
     * @param bool $deprecated Whether the parameter is deprecated
     * @param array<string, mixed> $schema Parameter schema
     * @param mixed $example Example value
     */
    public function __construct(
        public readonly string $name,
        public readonly string $in = 'query',
        public readonly ?string $description = null,
        public readonly bool $required = false,
        public readonly bool $deprecated = false,
        public readonly array $schema = [],
        public readonly mixed $example = null,
    ) {}

    /**
     * Convert attribute to OpenAPI parameter array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $parameter = [
            'name' => $this->name,
            'in' => $this->in,
        ];

        if ($this->description) {
            $parameter['description'] = $this->description;
        }

        if ($this->required) {
            $parameter['required'] = true;
        }

        if ($this->deprecated) {
            $parameter['deprecated'] = true;
        }

        if (!empty($this->schema)) {
            $parameter['schema'] = $this->schema;
        }

        if ($this->example !== null) {
            $parameter['example'] = $this->example;
        }

        return $parameter;
    }

    /**
     * Create a path parameter.
     */
    public static function path(
        string $name,
        array $schema = ['type' => 'string'],
        ?string $description = null,
    ): self {
        return new self(
            name: $name,
            in: 'path',
            required: true, // Path parameters are always required
            schema: $schema,
            description: $description,
        );
    }

    /**
     * Create a query parameter.
     */
    public static function query(
        string $name,
        array $schema = ['type' => 'string'],
        bool $required = false,
        ?string $description = null,
    ): self {
        return new self(
            name: $name,
            in: 'query',
            required: $required,
            schema: $schema,
            description: $description,
        );
    }

    /**
     * Create a header parameter.
     */
    public static function header(
        string $name,
        array $schema = ['type' => 'string'],
        bool $required = false,
        ?string $description = null,
    ): self {
        return new self(
            name: $name,
            in: 'header',
            required: $required,
            schema: $schema,
            description: $description,
        );
    }
}
