<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Contracts;

/**
 * Interface for exporting OpenAPI spec to different formats.
 */
interface ExporterInterface
{
    /**
     * Export OpenAPI spec to string.
     *
     * @param array<string, mixed> $spec
     */
    public function export(array $spec): string;

    /**
     * Get export format (json, yaml, etc).
     */
    public function getFormat(): string;
}
