<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Attributes;

use Attribute;

/**
 * Tag attribute for grouping operations.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Tag
{
    /**
     * @param string $name Tag name
     * @param string|null $description Tag description
     * @param array<string, mixed> $externalDocs External documentation
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly array $externalDocs = [],
    ) {}

    /**
     * Convert attribute to OpenAPI tag array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $tag = [
            'name' => $this->name,
        ];

        if ($this->description) {
            $tag['description'] = $this->description;
        }

        if (!empty($this->externalDocs)) {
            $tag['externalDocs'] = $this->externalDocs;
        }

        return $tag;
    }
}
