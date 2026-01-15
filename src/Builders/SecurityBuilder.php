<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Builders;

use HerolabID\LaravelOpenApi\Contracts\BuilderInterface;

/**
 * Builder for OpenAPI security schemes.
 */
class SecurityBuilder implements BuilderInterface
{
    /**
     * Build security schemes from configuration.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function build(array $data): array
    {
        if (empty($data)) {
            return [];
        }

        $schemes = [];

        foreach ($data as $name => $scheme) {
            $schemes[$name] = $this->buildScheme($scheme);
        }

        return $schemes;
    }

    /**
     * Build a single security scheme.
     *
     * @param array<string, mixed> $scheme
     * @return array<string, mixed>
     */
    private function buildScheme(array $scheme): array
    {
        $built = [
            'type' => $scheme['type'] ?? 'http',
        ];

        // Description
        if (isset($scheme['description'])) {
            $built['description'] = $scheme['description'];
        }

        // HTTP type
        if ($built['type'] === 'http') {
            if (isset($scheme['scheme'])) {
                $built['scheme'] = $scheme['scheme'];
            }

            if (isset($scheme['bearerFormat'])) {
                $built['bearerFormat'] = $scheme['bearerFormat'];
            }
        }

        // API Key type
        if ($built['type'] === 'apiKey') {
            if (isset($scheme['name'])) {
                $built['name'] = $scheme['name'];
            }

            if (isset($scheme['in'])) {
                $built['in'] = $scheme['in'];
            }
        }

        // OAuth2 type
        if ($built['type'] === 'oauth2') {
            if (isset($scheme['flows'])) {
                $built['flows'] = $scheme['flows'];
            }
        }

        // OpenID Connect type
        if ($built['type'] === 'openIdConnect') {
            if (isset($scheme['openIdConnectUrl'])) {
                $built['openIdConnectUrl'] = $scheme['openIdConnectUrl'];
            }
        }

        return $built;
    }

    /**
     * Auto-detect Laravel Sanctum security scheme.
     *
     * @return array<string, mixed>
     */
    public function detectSanctum(): array
    {
        if (!class_exists('Laravel\Sanctum\Sanctum')) {
            return [];
        }

        return [
            'sanctum' => [
                'type' => 'http',
                'scheme' => 'bearer',
                'bearerFormat' => 'Token',
                'description' => 'Laravel Sanctum token authentication',
            ],
        ];
    }

    /**
     * Auto-detect Laravel Passport security scheme.
     *
     * @return array<string, mixed>
     */
    public function detectPassport(): array
    {
        if (!class_exists('Laravel\Passport\Passport')) {
            return [];
        }

        return [
            'passport' => [
                'type' => 'oauth2',
                'description' => 'OAuth2 password grant',
                'flows' => [
                    'password' => [
                        'tokenUrl' => '/oauth/token',
                        'refreshUrl' => '/oauth/token',
                        'scopes' => [],
                    ],
                ],
            ],
        ];
    }

    /**
     * Auto-detect security schemes from Laravel packages.
     *
     * @return array<string, mixed>
     */
    public function autoDetect(): array
    {
        $schemes = [];

        // Detect Sanctum
        $sanctum = $this->detectSanctum();
        if (!empty($sanctum)) {
            $schemes = array_merge($schemes, $sanctum);
        }

        // Detect Passport
        $passport = $this->detectPassport();
        if (!empty($passport)) {
            $schemes = array_merge($schemes, $passport);
        }

        return $schemes;
    }
}
