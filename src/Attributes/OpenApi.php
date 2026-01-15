<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Attributes;

use Attribute;

/**
 * Root OpenAPI specification attribute.
 *
 * This attribute can be placed on a class to define the root OpenAPI spec.
 * Usually used on a dedicated OpenAPI configuration class.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class OpenApi
{
    /**
     * @param string $version OpenAPI spec version (default: 3.1.0)
     * @param array{title: string, version: string, description?: string} $info API information
     * @param array<string, mixed> $servers List of servers
     * @param array<string, mixed> $security Global security requirements
     * @param array<string, mixed> $tags List of tags
     */
    public function __construct(
        public readonly string $version = '3.1.0',
        public readonly array $info = [],
        public readonly array $servers = [],
        public readonly array $security = [],
        public readonly array $tags = [],
    ) {}

    /**
     * Convert attribute to OpenAPI spec array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $spec = [
            'openapi' => $this->version,
            'info' => $this->info,
        ];

        if (!empty($this->servers)) {
            $spec['servers'] = $this->servers;
        }

        if (!empty($this->security)) {
            $spec['security'] = $this->security;
        }

        if (!empty($this->tags)) {
            $spec['tags'] = $this->tags;
        }

        return $spec;
    }
}
