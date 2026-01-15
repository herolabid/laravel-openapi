<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Services;

use Psr\Log\LoggerInterface;
use HerolabID\LaravelOpenApi\Builders\SecurityBuilder;
use HerolabID\LaravelOpenApi\Builders\SpecBuilder;
use HerolabID\LaravelOpenApi\Contracts\CacheInterface;
use HerolabID\LaravelOpenApi\Parser\AttributeParser;
use HerolabID\LaravelOpenApi\Parser\ModelAnalyzer;
use HerolabID\LaravelOpenApi\Parser\RouteAnalyzer;

/**
 * Main service for generating OpenAPI specifications.
 *
 * Orchestrates the entire spec generation process:
 * 1. Scan controllers and models
 * 2. Parse PHP attributes
 * 3. Build OpenAPI spec structure
 * 4. Validate against OpenAPI 3.1 schema
 * 5. Cache the result
 */
class SpecGenerationService
{
    public function __construct(
        private readonly AttributeParser $attributeParser,
        private readonly ModelAnalyzer $modelAnalyzer,
        private readonly RouteAnalyzer $routeAnalyzer,
        private readonly SpecBuilder $specBuilder,
        private readonly SecurityBuilder $securityBuilder,
        private readonly ValidationService $validator,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Generate OpenAPI specification.
     *
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function generate(array $options = []): array
    {
        // Try cache first
        if (!($options['force'] ?? false)) {
            $cached = $this->getFromCache();
            if ($cached !== null) {
                $this->logger->info('Serving cached OpenAPI specification');
                return $cached;
            }
        }

        $this->logger->info('Generating fresh OpenAPI specification');

        // Get configuration
        $config = $this->getConfig();

        // Scan and parse
        $paths = $this->parsePaths($config['scan']['controllers']);
        $schemas = $this->parseSchemas($config['scan']['models']);

        // Build spec
        $spec = $this->buildSpec($config, $paths, $schemas);

        // Validate
        if ($options['validate'] ?? true) {
            $this->validator->validate($spec);
        }

        // Cache
        if ($config['cache']['enabled']) {
            $this->cache->put('openapi:spec', $spec, $config['cache']['ttl']);
            $this->logger->info('OpenAPI specification cached');
        }

        return $spec;
    }

    /**
     * Regenerate specification (bypass cache).
     *
     * @return array<string, mixed>
     */
    public function regenerate(): array
    {
        $this->cache->forget('openapi:spec');
        return $this->generate(['force' => true]);
    }

    /**
     * Get specification from cache.
     *
     * @return array<string, mixed>|null
     */
    private function getFromCache(): ?array
    {
        if (!$this->cache->has('openapi:spec')) {
            return null;
        }

        if ($this->cache->needsRegeneration()) {
            $this->logger->info('Cache invalidated due to file changes');
            return null;
        }

        return $this->cache->get('openapi:spec');
    }

    /**
     * Parse paths from controllers.
     *
     * @param array<string> $controllerPaths
     * @return array<string, mixed>
     */
    private function parsePaths(array $controllerPaths): array
    {
        $this->logger->debug('Parsing controllers', [
            'paths' => $controllerPaths,
        ]);

        // Get controller classes from routes
        $controllers = $this->routeAnalyzer->getControllerClasses();

        if (empty($controllers)) {
            // Fallback: scan directories
            $controllers = $this->scanControllers($controllerPaths);
        }

        $parsedPaths = $this->attributeParser->parseControllers($controllers);

        $this->logger->debug('Parsed paths', [
            'count' => count($parsedPaths),
        ]);

        return $parsedPaths;
    }

    /**
     * Parse schemas from models.
     *
     * @param array<string> $modelPaths
     * @return array<string, mixed>
     */
    private function parseSchemas(array $modelPaths): array
    {
        $this->logger->debug('Parsing models', [
            'paths' => $modelPaths,
        ]);

        $models = $this->scanModels($modelPaths);
        $schemas = $this->modelAnalyzer->analyzeMany($models);

        $this->logger->debug('Parsed schemas', [
            'count' => count($schemas),
        ]);

        return $schemas;
    }

    /**
     * Build OpenAPI specification.
     *
     * @param array<string, mixed> $config
     * @param array<string, mixed> $paths
     * @param array<string, mixed> $schemas
     * @return array<string, mixed>
     */
    private function buildSpec(array $config, array $paths, array $schemas): array
    {
        $this->logger->debug('Building OpenAPI specification');

        $this->specBuilder->reset();

        // Set basic info
        $this->specBuilder
            ->setInfo($config['info'])
            ->addPaths($paths)
            ->addSchemas($schemas);

        // Add security if auto-detect enabled
        if ($config['security']['auto_detect'] ?? true) {
            $this->specBuilder->addSecurity(
                $this->securityBuilder->autoDetect()
            );
        }

        // Add manual security schemes
        if (!empty($config['security']['schemes'])) {
            $this->specBuilder->addSecurity($config['security']['schemes']);
        }

        return $this->specBuilder->build();
    }

    /**
     * Get configuration.
     *
     * @return array<string, mixed>
     */
    private function getConfig(): array
    {
        return config('openapi', [
            'info' => [
                'title' => config('app.name', 'API Documentation'),
                'version' => '1.0.0',
            ],
            'scan' => [
                'controllers' => [app_path('Http/Controllers')],
                'models' => [app_path('Models')],
            ],
            'cache' => [
                'enabled' => !app()->environment('local'),
                'ttl' => 3600,
            ],
            'security' => [
                'auto_detect' => true,
                'schemes' => [],
            ],
        ]);
    }

    /**
     * Scan controller directories for classes.
     *
     * @param array<string> $paths
     * @return array<string>
     */
    private function scanControllers(array $paths): array
    {
        // This will be implemented with FileScanner
        return [];
    }

    /**
     * Scan model directories for classes.
     *
     * @param array<string> $paths
     * @return array<string>
     */
    private function scanModels(array $paths): array
    {
        // This will be implemented with FileScanner
        return [];
    }
}
