<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Parser;

use ReflectionClass;
use ReflectionMethod;
use HerolabID\LaravelOpenApi\Attributes\Operation;
use HerolabID\LaravelOpenApi\Attributes\Parameter;
use HerolabID\LaravelOpenApi\Attributes\RequestBody;
use HerolabID\LaravelOpenApi\Attributes\Response;
use HerolabID\LaravelOpenApi\Attributes\Tag;
use HerolabID\LaravelOpenApi\Contracts\ParserInterface;
use HerolabID\LaravelOpenApi\Exceptions\ParsingException;

/**
 * Parser for extracting OpenAPI metadata from PHP attributes.
 */
class AttributeParser implements ParserInterface
{
    public function __construct(
        private readonly ReflectionHelper $reflectionHelper,
        private readonly TypeResolver $typeResolver,
    ) {}

    /**
     * Parse controllers to extract OpenAPI paths.
     *
     * @param array<string> $paths
     * @return array<string, mixed>
     */
    public function parseControllers(array $paths): array
    {
        $parsedPaths = [];

        foreach ($paths as $controllerClass) {
            if (!class_exists($controllerClass)) {
                continue;
            }

            $reflection = new ReflectionClass($controllerClass);
            $methods = $this->reflectionHelper->getPublicMethods($reflection);

            foreach ($methods as $method) {
                $operations = $this->parseMethod($method);

                foreach ($operations as $operation) {
                    $path = $operation['path'];
                    $httpMethod = $operation['method'];

                    if (!isset($parsedPaths[$path])) {
                        $parsedPaths[$path] = [];
                    }

                    $parsedPaths[$path][$httpMethod] = $operation['operation'];
                }
            }
        }

        return $parsedPaths;
    }

    /**
     * Parse models to extract OpenAPI schemas.
     *
     * @param array<string> $paths
     * @return array<string, mixed>
     */
    public function parseModels(array $paths): array
    {
        // Will be implemented in ModelAnalyzer
        return [];
    }

    /**
     * Parse a single class to extract attributes.
     *
     * @return array<string, mixed>
     */
    public function parseClass(string $className): array
    {
        if (!class_exists($className)) {
            throw ParsingException::classNotFound($className);
        }

        $reflection = new ReflectionClass($className);
        $methods = $this->reflectionHelper->getPublicMethods($reflection);

        $result = [
            'paths' => [],
            'schemas' => [],
        ];

        foreach ($methods as $method) {
            $operations = $this->parseMethod($method);

            foreach ($operations as $operation) {
                $path = $operation['path'];
                $httpMethod = $operation['method'];

                if (!isset($result['paths'][$path])) {
                    $result['paths'][$path] = [];
                }

                $result['paths'][$path][$httpMethod] = $operation['operation'];
            }
        }

        return $result;
    }

    /**
     * Parse a method to extract operation attributes.
     *
     * @return array<array{path: string, method: string, operation: array<string, mixed>}>
     */
    private function parseMethod(ReflectionMethod $method): array
    {
        $operations = $this->reflectionHelper->getMethodAttributes(
            $method,
            Operation::class
        );

        if (empty($operations)) {
            return [];
        }

        $result = [];

        foreach ($operations as $operation) {
            /** @var Operation $operation */
            $operationData = $operation->toArray();

            // Add parameters
            $parameters = $this->parseParameters($method);
            if (!empty($parameters)) {
                $operationData['parameters'] = $parameters;
            }

            // Add request body
            $requestBody = $this->parseRequestBody($method);
            if ($requestBody !== null) {
                $operationData['requestBody'] = $requestBody;
            }

            // Add responses
            $responses = $this->parseResponses($method);
            if (!empty($responses)) {
                $operationData['responses'] = $responses;
            }

            $result[] = [
                'path' => $operation->path,
                'method' => $operation->getMethod(),
                'operation' => $operationData,
            ];
        }

        return $result;
    }

    /**
     * Parse parameter attributes from a method.
     *
     * @return array<array<string, mixed>>
     */
    private function parseParameters(ReflectionMethod $method): array
    {
        $parameters = $this->reflectionHelper->getMethodAttributes(
            $method,
            Parameter::class
        );

        return array_map(
            fn(Parameter $param) => $param->toArray(),
            $parameters
        );
    }

    /**
     * Parse request body attribute from a method.
     *
     * @return array<string, mixed>|null
     */
    private function parseRequestBody(ReflectionMethod $method): ?array
    {
        $requestBodies = $this->reflectionHelper->getMethodAttributes(
            $method,
            RequestBody::class
        );

        if (empty($requestBodies)) {
            return null;
        }

        return $requestBodies[0]->toArray();
    }

    /**
     * Parse response attributes from a method.
     *
     * @return array<string, array<string, mixed>>
     */
    private function parseResponses(ReflectionMethod $method): array
    {
        $responses = $this->reflectionHelper->getMethodAttributes(
            $method,
            Response::class
        );

        if (empty($responses)) {
            // Default 200 response if none specified
            return [
                '200' => [
                    'description' => 'Success',
                ],
            ];
        }

        $result = [];

        foreach ($responses as $response) {
            /** @var Response $response */
            $result[(string) $response->status] = $response->toArray();
        }

        return $result;
    }

    /**
     * Parse tag attributes from a class or method.
     *
     * @return array<string>
     */
    public function parseTags(ReflectionClass|ReflectionMethod $reflection): array
    {
        $tags = [];

        if ($reflection instanceof ReflectionClass) {
            $tagAttributes = $this->reflectionHelper->getClassAttributes(
                $reflection,
                Tag::class
            );
        } else {
            $tagAttributes = $this->reflectionHelper->getMethodAttributes(
                $reflection,
                Tag::class
            );
        }

        foreach ($tagAttributes as $tag) {
            /** @var Tag $tag */
            $tags[] = $tag->name;
        }

        return $tags;
    }

    /**
     * Get operation ID from method.
     * If not specified in attribute, generate from class and method name.
     */
    private function getOperationId(ReflectionMethod $method, ?string $operationId): string
    {
        if ($operationId !== null) {
            return $operationId;
        }

        $className = $method->getDeclaringClass()->getShortName();
        $methodName = $method->getName();

        // Convert to camelCase
        $className = lcfirst(str_replace('Controller', '', $className));
        return $className . ucfirst($methodName);
    }
}
