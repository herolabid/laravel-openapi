<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Attributes;

use Attribute;

/**
 * Base class for HTTP operation attributes.
 */
#[Attribute(Attribute::TARGET_METHOD)]
abstract class Operation
{
    /**
     * @param string $path The path for this operation
     * @param string $summary Short summary of the operation
     * @param string|null $description Detailed description
     * @param string|null $operationId Unique operation identifier
     * @param array<string> $tags List of tags
     * @param bool $deprecated Whether the operation is deprecated
     * @param array<string, mixed> $security Security requirements
     */
    public function __construct(
        public readonly string $path,
        public readonly string $summary = '',
        public readonly ?string $description = null,
        public readonly ?string $operationId = null,
        public readonly array $tags = [],
        public readonly bool $deprecated = false,
        public readonly array $security = [],
    ) {}

    /**
     * Get the HTTP method for this operation.
     */
    abstract public function getMethod(): string;

    /**
     * Convert attribute to OpenAPI operation array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $operation = [];

        if ($this->summary) {
            $operation['summary'] = $this->summary;
        }

        if ($this->description) {
            $operation['description'] = $this->description;
        }

        if ($this->operationId) {
            $operation['operationId'] = $this->operationId;
        }

        if (!empty($this->tags)) {
            $operation['tags'] = $this->tags;
        }

        if ($this->deprecated) {
            $operation['deprecated'] = true;
        }

        if (!empty($this->security)) {
            $operation['security'] = $this->security;
        }

        return $operation;
    }
}
