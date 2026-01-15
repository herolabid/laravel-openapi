<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Attributes;

use Attribute;

/**
 * Response attribute for operation responses.
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Response
{
    /**
     * @param int $status HTTP status code
     * @param string $description Response description
     * @param array<string, mixed>|Schema|null $content Response content by media type
     * @param string $contentType Default content type (default: application/json)
     * @param array<string, mixed> $headers Response headers
     */
    public function __construct(
        public readonly int $status,
        public readonly string $description = '',
        public readonly array|Schema|null $content = null,
        public readonly string $contentType = 'application/json',
        public readonly array $headers = [],
    ) {}

    /**
     * Convert attribute to OpenAPI response array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $response = [
            'description' => $this->description ?: $this->getDefaultDescription(),
        ];

        if ($this->content !== null) {
            if ($this->content instanceof Schema) {
                $response['content'] = [
                    $this->contentType => [
                        'schema' => $this->content->toArray(),
                    ],
                ];
            } elseif (is_array($this->content)) {
                $response['content'] = $this->content;
            }
        }

        if (!empty($this->headers)) {
            $response['headers'] = $this->headers;
        }

        return $response;
    }

    /**
     * Get default description based on status code.
     */
    private function getDefaultDescription(): string
    {
        return match ($this->status) {
            200 => 'Success',
            201 => 'Created',
            202 => 'Accepted',
            204 => 'No Content',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            422 => 'Validation Error',
            500 => 'Internal Server Error',
            default => 'Response',
        };
    }

    /**
     * Create a successful JSON response (200).
     */
    public static function ok(
        Schema|array|null $content = null,
        string $description = 'Success',
    ): self {
        return new self(
            status: 200,
            description: $description,
            content: $content,
        );
    }

    /**
     * Create a created response (201).
     */
    public static function created(
        Schema|array|null $content = null,
        string $description = 'Resource created',
    ): self {
        return new self(
            status: 201,
            description: $description,
            content: $content,
        );
    }

    /**
     * Create a no content response (204).
     */
    public static function noContent(
        string $description = 'No content',
    ): self {
        return new self(
            status: 204,
            description: $description,
        );
    }

    /**
     * Create a not found response (404).
     */
    public static function notFound(
        string $description = 'Resource not found',
    ): self {
        return new self(
            status: 404,
            description: $description,
        );
    }

    /**
     * Create a validation error response (422).
     */
    public static function validationError(
        string $description = 'Validation failed',
    ): self {
        return new self(
            status: 422,
            description: $description,
        );
    }
}
