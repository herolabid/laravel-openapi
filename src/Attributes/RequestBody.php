<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Attributes;

use Attribute;

/**
 * Request body attribute for POST, PUT, PATCH operations.
 */
#[Attribute(Attribute::TARGET_METHOD)]
class RequestBody
{
    /**
     * @param string|null $description Request body description
     * @param bool $required Whether the request body is required
     * @param array<string, mixed>|Schema|null $content Content schemas by media type
     * @param string $contentType Default content type (default: application/json)
     */
    public function __construct(
        public readonly ?string $description = null,
        public readonly bool $required = true,
        public readonly array|Schema|null $content = null,
        public readonly string $contentType = 'application/json',
    ) {}

    /**
     * Convert attribute to OpenAPI request body array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $requestBody = [];

        if ($this->description) {
            $requestBody['description'] = $this->description;
        }

        if ($this->required) {
            $requestBody['required'] = true;
        }

        if ($this->content !== null) {
            if ($this->content instanceof Schema) {
                $requestBody['content'] = [
                    $this->contentType => [
                        'schema' => $this->content->toArray(),
                    ],
                ];
            } elseif (is_array($this->content)) {
                $requestBody['content'] = $this->content;
            }
        }

        return $requestBody;
    }

    /**
     * Create a JSON request body.
     */
    public static function json(
        Schema|array $schema,
        bool $required = true,
        ?string $description = null,
    ): self {
        return new self(
            description: $description,
            required: $required,
            content: $schema,
            contentType: 'application/json',
        );
    }

    /**
     * Create a form data request body.
     */
    public static function formData(
        array $schema,
        bool $required = true,
        ?string $description = null,
    ): self {
        return new self(
            description: $description,
            required: $required,
            content: $schema,
            contentType: 'multipart/form-data',
        );
    }
}
