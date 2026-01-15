<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Attributes;

use Attribute;

/**
 * Security scheme attribute for defining authentication methods.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class SecurityScheme
{
    /**
     * @param string $name Security scheme name
     * @param string $type Security type: 'http', 'apiKey', 'oauth2', 'openIdConnect'
     * @param string|null $scheme HTTP auth scheme (bearer, basic, etc)
     * @param string|null $bearerFormat Bearer token format (e.g., JWT)
     * @param string|null $in API key location: 'query', 'header', 'cookie'
     * @param string|null $keyName API key parameter name
     * @param string|null $description Security scheme description
     * @param array<string, mixed> $flows OAuth2 flows
     * @param string|null $openIdConnectUrl OpenID Connect URL
     */
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly ?string $scheme = null,
        public readonly ?string $bearerFormat = null,
        public readonly ?string $in = null,
        public readonly ?string $keyName = null,
        public readonly ?string $description = null,
        public readonly array $flows = [],
        public readonly ?string $openIdConnectUrl = null,
    ) {}

    /**
     * Convert attribute to OpenAPI security scheme array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $scheme = [
            'type' => $this->type,
        ];

        if ($this->description) {
            $scheme['description'] = $this->description;
        }

        // HTTP scheme
        if ($this->type === 'http' && $this->scheme) {
            $scheme['scheme'] = $this->scheme;

            if ($this->bearerFormat) {
                $scheme['bearerFormat'] = $this->bearerFormat;
            }
        }

        // API Key scheme
        if ($this->type === 'apiKey') {
            if ($this->in) {
                $scheme['in'] = $this->in;
            }
            if ($this->keyName) {
                $scheme['name'] = $this->keyName;
            }
        }

        // OAuth2 scheme
        if ($this->type === 'oauth2' && !empty($this->flows)) {
            $scheme['flows'] = $this->flows;
        }

        // OpenID Connect scheme
        if ($this->type === 'openIdConnect' && $this->openIdConnectUrl) {
            $scheme['openIdConnectUrl'] = $this->openIdConnectUrl;
        }

        return $scheme;
    }

    /**
     * Create a Bearer token security scheme.
     */
    public static function bearer(
        string $name = 'bearer',
        string $bearerFormat = 'JWT',
        ?string $description = null,
    ): self {
        return new self(
            name: $name,
            type: 'http',
            scheme: 'bearer',
            bearerFormat: $bearerFormat,
            description: $description ?? 'Bearer token authentication',
        );
    }

    /**
     * Create a Basic auth security scheme.
     */
    public static function basic(
        string $name = 'basic',
        ?string $description = null,
    ): self {
        return new self(
            name: $name,
            type: 'http',
            scheme: 'basic',
            description: $description ?? 'Basic HTTP authentication',
        );
    }

    /**
     * Create an API Key security scheme.
     */
    public static function apiKey(
        string $name = 'apiKey',
        string $keyName = 'X-API-Key',
        string $in = 'header',
        ?string $description = null,
    ): self {
        return new self(
            name: $name,
            type: 'apiKey',
            in: $in,
            keyName: $keyName,
            description: $description ?? 'API Key authentication',
        );
    }
}
