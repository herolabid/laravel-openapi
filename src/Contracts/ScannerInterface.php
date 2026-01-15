<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Contracts;

/**
 * Interface for scanning files and directories.
 */
interface ScannerInterface
{
    /**
     * Scan directories for PHP files.
     *
     * @param array<string> $paths
     * @return array<string>
     */
    public function scan(array $paths): array;

    /**
     * Get relevant files that may contain OpenAPI metadata.
     *
     * @return array<string>
     */
    public function getRelevantFiles(): array;
}
