<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Builders;

use HerolabID\LaravelOpenApi\Contracts\BuilderInterface;

/**
 * Main OpenAPI specification builder.
 *
 * Uses builder pattern to construct a complete OpenAPI 3.1 specification.
 */
class SpecBuilder implements BuilderInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $spec = [];

    public function __construct(
        private readonly PathBuilder $pathBuilder,
        private readonly SchemaBuilder $schemaBuilder,
        private readonly SecurityBuilder $securityBuilder,
    ) {
        $this->reset();
    }

    /**
     * Reset the builder to initial state.
     */
    public function reset(): self
    {
        $this->spec = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'API Documentation',
                'version' => '1.0.0',
            ],
            'paths' => [],
            'components' => [],
        ];

        return $this;
    }

    /**
     * Set OpenAPI version.
     */
    public function setVersion(string $version): self
    {
        $this->spec['openapi'] = $version;
        return $this;
    }

    /**
     * Set API information.
     *
     * @param array{title: string, version: string, description?: string} $info
     */
    public function setInfo(array $info): self
    {
        $this->spec['info'] = array_merge($this->spec['info'], $info);
        return $this;
    }

    /**
     * Set servers.
     *
     * @param array<array{url: string, description?: string}> $servers
     */
    public function setServers(array $servers): self
    {
        $this->spec['servers'] = $servers;
        return $this;
    }

    /**
     * Add paths from parsed controller data.
     *
     * @param array<string, mixed> $pathsData
     */
    public function addPaths(array $pathsData): self
    {
        $paths = $this->pathBuilder->build($pathsData);

        $this->spec['paths'] = array_merge(
            $this->spec['paths'],
            $paths
        );

        return $this;
    }

    /**
     * Add schemas from parsed model data.
     *
     * @param array<string, mixed> $schemasData
     */
    public function addSchemas(array $schemasData): self
    {
        $schemas = $this->schemaBuilder->build($schemasData);

        if (!isset($this->spec['components']['schemas'])) {
            $this->spec['components']['schemas'] = [];
        }

        $this->spec['components']['schemas'] = array_merge(
            $this->spec['components']['schemas'],
            $schemas
        );

        return $this;
    }

    /**
     * Add security schemes.
     *
     * @param array<string, mixed> $securityData
     */
    public function addSecurity(array $securityData): self
    {
        $security = $this->securityBuilder->build($securityData);

        if (!empty($security)) {
            $this->spec['components']['securitySchemes'] = $security;
        }

        return $this;
    }

    /**
     * Set global security requirements.
     *
     * @param array<array<string, array<string>>> $security
     */
    public function setGlobalSecurity(array $security): self
    {
        $this->spec['security'] = $security;
        return $this;
    }

    /**
     * Add tags.
     *
     * @param array<array{name: string, description?: string}> $tags
     */
    public function addTags(array $tags): self
    {
        if (!isset($this->spec['tags'])) {
            $this->spec['tags'] = [];
        }

        $this->spec['tags'] = array_merge($this->spec['tags'], $tags);
        return $this;
    }

    /**
     * Build the final OpenAPI specification.
     *
     * @return array<string, mixed>
     */
    public function build(array $data = []): array
    {
        // Clean up empty sections
        if (empty($this->spec['paths'])) {
            unset($this->spec['paths']);
        }

        if (empty($this->spec['components'])) {
            unset($this->spec['components']);
        }

        if (isset($this->spec['components']) && empty($this->spec['components']['schemas'])) {
            unset($this->spec['components']['schemas']);
        }

        if (isset($this->spec['components']) && empty($this->spec['components']['securitySchemes'])) {
            unset($this->spec['components']['securitySchemes']);
        }

        return $this->spec;
    }

    /**
     * Get current spec (without cleanup).
     *
     * @return array<string, mixed>
     */
    public function getSpec(): array
    {
        return $this->spec;
    }

    /**
     * Validate that required fields are present.
     */
    public function validate(): bool
    {
        if (!isset($this->spec['openapi'])) {
            return false;
        }

        if (!isset($this->spec['info']['title']) || !isset($this->spec['info']['version'])) {
            return false;
        }

        return true;
    }

    /**
     * Create a new builder instance.
     */
    public static function make(
        PathBuilder $pathBuilder,
        SchemaBuilder $schemaBuilder,
        SecurityBuilder $securityBuilder,
    ): self {
        return new self($pathBuilder, $schemaBuilder, $securityBuilder);
    }
}
