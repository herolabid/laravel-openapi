<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Services;

/**
 * Service for handling API schema versioning.
 *
 * Supports multiple API versions with separate specifications.
 */
class VersioningService
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private array $config = []
    ) {}

    /**
     * Get all configured API versions.
     *
     * @return array<string>
     */
    public function getVersions(): array
    {
        return array_keys($this->config['versions'] ?? []);
    }

    /**
     * Get default version.
     */
    public function getDefaultVersion(): ?string
    {
        return $this->config['default'] ?? null;
    }

    /**
     * Get configuration for specific version.
     *
     * @return array<string, mixed>|null
     */
    public function getVersionConfig(string $version): ?array
    {
        return $this->config['versions'][$version] ?? null;
    }

    /**
     * Check if version exists.
     */
    public function hasVersion(string $version): bool
    {
        return isset($this->config['versions'][$version]);
    }

    /**
     * Get paths to scan for specific version.
     *
     * @return array{controllers: array<string>, models: array<string>}
     */
    public function getVersionPaths(string $version): array
    {
        $versionConfig = $this->getVersionConfig($version);

        if ($versionConfig === null) {
            return ['controllers' => [], 'models' => []];
        }

        return [
            'controllers' => $versionConfig['scan']['controllers'] ?? [],
            'models' => $versionConfig['scan']['models'] ?? [],
        ];
    }

    /**
     * Get info for specific version.
     *
     * @return array<string, mixed>
     */
    public function getVersionInfo(string $version): array
    {
        $versionConfig = $this->getVersionConfig($version);

        if ($versionConfig === null) {
            return [];
        }

        return $versionConfig['info'] ?? [];
    }

    /**
     * Build versioned spec configuration.
     *
     * @return array<string, mixed>
     */
    public function buildVersionedConfig(string $version): array
    {
        $versionConfig = $this->getVersionConfig($version);

        if ($versionConfig === null) {
            return [];
        }

        // Merge with base config
        $baseConfig = $this->config['base'] ?? [];

        return array_merge_recursive($baseConfig, $versionConfig);
    }

    /**
     * Get URL path prefix for version.
     */
    public function getVersionPrefix(string $version): string
    {
        $versionConfig = $this->getVersionConfig($version);

        if ($versionConfig === null) {
            return '';
        }

        return $versionConfig['prefix'] ?? "/v{$version}";
    }

    /**
     * Parse version from request path.
     */
    public function parseVersionFromPath(string $path): ?string
    {
        // Match /v1/, /v2/, /api/v1/, etc.
        if (preg_match('/\/v(\d+)\//', $path, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Get servers configuration for version.
     *
     * @return array<array<string, mixed>>
     */
    public function getVersionServers(string $version): array
    {
        $versionConfig = $this->getVersionConfig($version);

        if ($versionConfig === null) {
            return [];
        }

        $servers = $versionConfig['servers'] ?? [];

        // If no servers defined, generate default
        if (empty($servers)) {
            $prefix = $this->getVersionPrefix($version);

            return [
                [
                    'url' => config('app.url') . $prefix,
                    'description' => "API version {$version}",
                ],
            ];
        }

        return $servers;
    }

    /**
     * Check if versioning is enabled.
     */
    public function isEnabled(): bool
    {
        return (bool) ($this->config['enabled'] ?? false);
    }

    /**
     * Get cache key for versioned spec.
     */
    public function getCacheKey(string $version): string
    {
        return "openapi:spec:v{$version}";
    }

    /**
     * Validate version format.
     */
    public function isValidVersion(string $version): bool
    {
        // Accept: 1, 2, 1.0, 1.1, v1, v2, etc.
        return (bool) preg_match('/^v?\d+(\.\d+)?$/', $version);
    }

    /**
     * Normalize version format (remove 'v' prefix if exists).
     */
    public function normalizeVersion(string $version): string
    {
        return ltrim($version, 'v');
    }

    /**
     * Compare two versions.
     *
     * @return int Returns < 0 if $version1 < $version2, 0 if equal, > 0 if $version1 > $version2
     */
    public function compareVersions(string $version1, string $version2): int
    {
        $v1 = $this->normalizeVersion($version1);
        $v2 = $this->normalizeVersion($version2);

        return version_compare($v1, $v2);
    }

    /**
     * Get latest version.
     */
    public function getLatestVersion(): ?string
    {
        $versions = $this->getVersions();

        if (empty($versions)) {
            return null;
        }

        usort($versions, [$this, 'compareVersions']);

        return end($versions);
    }

    /**
     * Get deprecated versions.
     *
     * @return array<string>
     */
    public function getDeprecatedVersions(): array
    {
        $deprecated = [];

        foreach ($this->config['versions'] ?? [] as $version => $config) {
            if ($config['deprecated'] ?? false) {
                $deprecated[] = $version;
            }
        }

        return $deprecated;
    }

    /**
     * Check if version is deprecated.
     */
    public function isDeprecated(string $version): bool
    {
        $versionConfig = $this->getVersionConfig($version);

        if ($versionConfig === null) {
            return false;
        }

        return (bool) ($versionConfig['deprecated'] ?? false);
    }

    /**
     * Get deprecation info for version.
     *
     * @return array<string, mixed>|null
     */
    public function getDeprecationInfo(string $version): ?array
    {
        if (!$this->isDeprecated($version)) {
            return null;
        }

        $versionConfig = $this->getVersionConfig($version);

        return [
            'version' => $version,
            'deprecated' => true,
            'sunset_date' => $versionConfig['sunset_date'] ?? null,
            'migration_guide' => $versionConfig['migration_guide'] ?? null,
            'replacement_version' => $versionConfig['replacement_version'] ?? $this->getLatestVersion(),
        ];
    }
}
