<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Builders;

use HerolabID\LaravelOpenApi\Contracts\BuilderInterface;

/**
 * Builder for OpenAPI response objects.
 */
class ResponseBuilder implements BuilderInterface
{
    /**
     * Build responses from parsed data.
     *
     * @param array<string, array<string, mixed>> $data
     * @return array<string, array<string, mixed>>
     */
    public function build(array $data): array
    {
        if (empty($data)) {
            return $this->getDefaultResponses();
        }

        $responses = [];

        foreach ($data as $statusCode => $response) {
            $responses[$statusCode] = $this->buildResponse($response);
        }

        return $responses;
    }

    /**
     * Build a single response object.
     *
     * @param array<string, mixed> $response
     * @return array<string, mixed>
     */
    private function buildResponse(array $response): array
    {
        $built = [
            'description' => $response['description'] ?? 'Response',
        ];

        // Content
        if (isset($response['content'])) {
            $built['content'] = $response['content'];
        }

        // Headers
        if (isset($response['headers']) && !empty($response['headers'])) {
            $built['headers'] = $response['headers'];
        }

        // Links
        if (isset($response['links']) && !empty($response['links'])) {
            $built['links'] = $response['links'];
        }

        return $built;
    }

    /**
     * Get default responses when none are specified.
     *
     * @return array<string, array<string, mixed>>
     */
    private function getDefaultResponses(): array
    {
        return [
            '200' => [
                'description' => 'Success',
            ],
        ];
    }

    /**
     * Add common error responses.
     *
     * @param array<string, array<string, mixed>> $responses
     * @return array<string, array<string, mixed>>
     */
    public function addCommonErrors(array $responses): array
    {
        $commonErrors = [
            '400' => [
                'description' => 'Bad Request',
            ],
            '401' => [
                'description' => 'Unauthorized',
            ],
            '403' => [
                'description' => 'Forbidden',
            ],
            '404' => [
                'description' => 'Not Found',
            ],
            '422' => [
                'description' => 'Validation Error',
            ],
            '500' => [
                'description' => 'Internal Server Error',
            ],
        ];

        // Only add errors that don't exist
        foreach ($commonErrors as $code => $error) {
            if (!isset($responses[$code])) {
                $responses[$code] = $error;
            }
        }

        return $responses;
    }

    /**
     * Sort responses by status code.
     *
     * @param array<string, array<string, mixed>> $responses
     * @return array<string, array<string, mixed>>
     */
    public function sort(array $responses): array
    {
        ksort($responses, SORT_NUMERIC);
        return $responses;
    }
}
