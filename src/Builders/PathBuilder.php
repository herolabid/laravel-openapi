<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Builders;

use HerolabID\LaravelOpenApi\Contracts\BuilderInterface;

/**
 * Builder for OpenAPI paths section.
 */
class PathBuilder implements BuilderInterface
{
    public function __construct(
        private readonly ParameterBuilder $parameterBuilder,
        private readonly ResponseBuilder $responseBuilder,
    ) {}

    /**
     * Build paths from parsed controller data.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function build(array $data): array
    {
        if (empty($data)) {
            return [];
        }

        $paths = [];

        foreach ($data as $path => $operations) {
            $paths[$path] = $this->buildPathItem($operations);
        }

        return $paths;
    }

    /**
     * Build a path item with operations.
     *
     * @param array<string, mixed> $operations
     * @return array<string, mixed>
     */
    private function buildPathItem(array $operations): array
    {
        $pathItem = [];

        foreach ($operations as $method => $operation) {
            $pathItem[$method] = $this->buildOperation($operation);
        }

        return $pathItem;
    }

    /**
     * Build an operation object.
     *
     * @param array<string, mixed> $operation
     * @return array<string, mixed>
     */
    private function buildOperation(array $operation): array
    {
        $built = [];

        // Summary and description
        if (isset($operation['summary'])) {
            $built['summary'] = $operation['summary'];
        }

        if (isset($operation['description'])) {
            $built['description'] = $operation['description'];
        }

        // Operation ID
        if (isset($operation['operationId'])) {
            $built['operationId'] = $operation['operationId'];
        }

        // Tags
        if (isset($operation['tags']) && !empty($operation['tags'])) {
            $built['tags'] = $operation['tags'];
        }

        // Parameters
        if (isset($operation['parameters']) && !empty($operation['parameters'])) {
            $built['parameters'] = $this->parameterBuilder->build(
                $operation['parameters']
            );
        }

        // Request body
        if (isset($operation['requestBody'])) {
            $built['requestBody'] = $this->buildRequestBody($operation['requestBody']);
        }

        // Responses
        if (isset($operation['responses']) && !empty($operation['responses'])) {
            $built['responses'] = $this->responseBuilder->build(
                $operation['responses']
            );
        }

        // Security
        if (isset($operation['security']) && !empty($operation['security'])) {
            $built['security'] = $operation['security'];
        }

        // Deprecated
        if (isset($operation['deprecated']) && $operation['deprecated']) {
            $built['deprecated'] = true;
        }

        return $built;
    }

    /**
     * Build request body object.
     *
     * @param array<string, mixed> $requestBody
     * @return array<string, mixed>
     */
    private function buildRequestBody(array $requestBody): array
    {
        $built = [];

        if (isset($requestBody['description'])) {
            $built['description'] = $requestBody['description'];
        }

        if (isset($requestBody['required'])) {
            $built['required'] = $requestBody['required'];
        }

        if (isset($requestBody['content'])) {
            $built['content'] = $requestBody['content'];
        }

        return $built;
    }

    /**
     * Merge multiple path collections.
     *
     * @param array<array<string, mixed>> $pathCollections
     * @return array<string, mixed>
     */
    public function merge(array $pathCollections): array
    {
        $merged = [];

        foreach ($pathCollections as $paths) {
            foreach ($paths as $path => $operations) {
                if (!isset($merged[$path])) {
                    $merged[$path] = [];
                }

                $merged[$path] = array_merge($merged[$path], $operations);
            }
        }

        return $merged;
    }
}
