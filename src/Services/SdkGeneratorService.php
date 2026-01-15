<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Services;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Service for generating client SDKs from OpenAPI specification.
 *
 * Uses openapi-generator-cli to generate SDKs for various languages.
 */
class SdkGeneratorService
{
    /**
     * Supported SDK generators.
     *
     * @var array<string, array<string, string>>
     */
    private array $generators = [
        'typescript' => [
            'generator' => 'typescript-axios',
            'description' => 'TypeScript with Axios HTTP client',
        ],
        'php' => [
            'generator' => 'php',
            'description' => 'PHP client library',
        ],
        'javascript' => [
            'generator' => 'javascript',
            'description' => 'JavaScript client library',
        ],
        'python' => [
            'generator' => 'python',
            'description' => 'Python client library',
        ],
        'java' => [
            'generator' => 'java',
            'description' => 'Java client library',
        ],
        'go' => [
            'generator' => 'go',
            'description' => 'Go client library',
        ],
        'ruby' => [
            'generator' => 'ruby',
            'description' => 'Ruby client library',
        ],
        'swift' => [
            'generator' => 'swift5',
            'description' => 'Swift 5 client library',
        ],
        'kotlin' => [
            'generator' => 'kotlin',
            'description' => 'Kotlin client library',
        ],
    ];

    public function __construct(
        private readonly SpecGenerationService $specGenerator,
    ) {}

    /**
     * Generate SDK for specified language.
     *
     * @param array<string, mixed> $options
     */
    public function generate(string $language, string $outputPath, array $options = []): bool
    {
        if (!$this->isSupported($language)) {
            throw new \InvalidArgumentException("Unsupported SDK language: {$language}");
        }

        // Generate OpenAPI spec
        $spec = $this->specGenerator->generate();
        $specPath = $this->saveSpecToTempFile($spec);

        try {
            // Use openapi-generator if available
            if ($this->hasOpenApiGenerator()) {
                return $this->generateWithOpenApiGenerator($language, $specPath, $outputPath, $options);
            }

            // Fallback: use Docker image
            if ($this->hasDocker()) {
                return $this->generateWithDocker($language, $specPath, $outputPath, $options);
            }

            throw new \RuntimeException(
                'Neither openapi-generator-cli nor Docker is available. ' .
                'Please install one of them to generate SDKs.'
            );
        } finally {
            // Clean up temp spec file
            if (file_exists($specPath)) {
                unlink($specPath);
            }
        }
    }

    /**
     * Generate SDK using openapi-generator-cli.
     *
     * @param array<string, mixed> $options
     */
    private function generateWithOpenApiGenerator(
        string $language,
        string $specPath,
        string $outputPath,
        array $options
    ): bool {
        $generator = $this->generators[$language]['generator'];

        $command = [
            'openapi-generator-cli',
            'generate',
            '-i', $specPath,
            '-g', $generator,
            '-o', $outputPath,
        ];

        // Add package name if provided
        if (isset($options['package_name'])) {
            $command[] = '--package-name';
            $command[] = $options['package_name'];
        }

        // Add additional properties
        if (isset($options['additional_properties'])) {
            $command[] = '--additional-properties';
            $command[] = $options['additional_properties'];
        }

        return $this->runCommand($command);
    }

    /**
     * Generate SDK using Docker.
     *
     * @param array<string, mixed> $options
     */
    private function generateWithDocker(
        string $language,
        string $specPath,
        string $outputPath,
        array $options
    ): bool {
        $generator = $this->generators[$language]['generator'];

        $command = [
            'docker', 'run', '--rm',
            '-v', $specPath . ':/spec.json',
            '-v', $outputPath . ':/output',
            'openapitools/openapi-generator-cli',
            'generate',
            '-i', '/spec.json',
            '-g', $generator,
            '-o', '/output',
        ];

        // Add package name if provided
        if (isset($options['package_name'])) {
            $command[] = '--package-name';
            $command[] = $options['package_name'];
        }

        return $this->runCommand($command);
    }

    /**
     * Run command using Symfony Process.
     *
     * @param array<string> $command
     */
    private function runCommand(array $command): bool
    {
        $process = new Process($command);
        $process->setTimeout(300); // 5 minutes

        try {
            $process->mustRun();
            return true;
        } catch (ProcessFailedException $e) {
            throw new \RuntimeException(
                "SDK generation failed: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Save OpenAPI spec to temporary file.
     *
     * @param array<string, mixed> $spec
     */
    private function saveSpecToTempFile(array $spec): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'openapi_') . '.json';
        file_put_contents($tempFile, json_encode($spec, JSON_PRETTY_PRINT));

        return $tempFile;
    }

    /**
     * Check if openapi-generator-cli is available.
     */
    private function hasOpenApiGenerator(): bool
    {
        $process = Process::fromShellCommandline('which openapi-generator-cli');
        $process->run();

        return $process->isSuccessful();
    }

    /**
     * Check if Docker is available.
     */
    private function hasDocker(): bool
    {
        $process = Process::fromShellCommandline('which docker');
        $process->run();

        return $process->isSuccessful();
    }

    /**
     * Check if language is supported.
     */
    public function isSupported(string $language): bool
    {
        return isset($this->generators[$language]);
    }

    /**
     * Get all supported languages.
     *
     * @return array<string, array<string, string>>
     */
    public function getSupportedLanguages(): array
    {
        return $this->generators;
    }

    /**
     * Get generator info for language.
     *
     * @return array<string, string>|null
     */
    public function getGeneratorInfo(string $language): ?array
    {
        return $this->generators[$language] ?? null;
    }

    /**
     * Generate SDK configuration file.
     *
     * @param array<string, mixed> $config
     */
    public function generateConfigFile(string $language, string $outputPath, array $config): bool
    {
        $configFile = $outputPath . '/openapi-generator-config.json';

        $configData = [
            'generatorName' => $this->generators[$language]['generator'],
            'outputDir' => $outputPath,
        ];

        $configData = array_merge($configData, $config);

        return file_put_contents(
            $configFile,
            json_encode($configData, JSON_PRETTY_PRINT)
        ) !== false;
    }

    /**
     * Validate output directory.
     */
    private function validateOutputDirectory(string $path): void
    {
        if (!is_dir($path)) {
            if (!mkdir($path, 0755, true)) {
                throw new \RuntimeException("Failed to create output directory: {$path}");
            }
        }

        if (!is_writable($path)) {
            throw new \RuntimeException("Output directory is not writable: {$path}");
        }
    }

    /**
     * Generate SDK with validation.
     *
     * @param array<string, mixed> $options
     */
    public function generateWithValidation(string $language, string $outputPath, array $options = []): bool
    {
        $this->validateOutputDirectory($outputPath);

        return $this->generate($language, $outputPath, $options);
    }

    /**
     * Generate multiple SDKs at once.
     *
     * @param array<string> $languages
     * @param array<string, mixed> $options
     * @return array<string, bool> Language => success status
     */
    public function generateMultiple(array $languages, string $baseOutputPath, array $options = []): array
    {
        $results = [];

        foreach ($languages as $language) {
            $outputPath = $baseOutputPath . '/' . $language;

            try {
                $results[$language] = $this->generateWithValidation($language, $outputPath, $options);
            } catch (\Exception $e) {
                $results[$language] = false;
            }
        }

        return $results;
    }
}
