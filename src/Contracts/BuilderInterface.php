<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Contracts;

/**
 * Interface for building parts of OpenAPI specification.
 */
interface BuilderInterface
{
    /**
     * Build the spec component from parsed data.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function build(array $data): array;
}
