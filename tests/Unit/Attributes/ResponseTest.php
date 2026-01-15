<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Tests\Unit\Attributes;

use PHPUnit\Framework\TestCase;
use HerolabID\LaravelOpenApi\Attributes\Response;
use HerolabID\LaravelOpenApi\Attributes\Schema;

class ResponseTest extends TestCase
{
    /** @test */
    public function response_converts_to_array_with_description(): void
    {
        $response = new Response(
            status: 200,
            description: 'Success',
        );

        $array = $response->toArray();

        $this->assertEquals('Success', $array['description']);
    }

    /** @test */
    public function response_uses_default_description_if_empty(): void
    {
        $response = new Response(status: 404);

        $array = $response->toArray();

        $this->assertEquals('Not Found', $array['description']);
    }

    /** @test */
    public function response_with_schema_content(): void
    {
        $response = new Response(
            status: 200,
            description: 'User found',
            content: new Schema(ref: 'User'),
        );

        $array = $response->toArray();

        $this->assertArrayHasKey('content', $array);
        $this->assertArrayHasKey('application/json', $array['content']);
        $this->assertEquals(
            '#/components/schemas/User',
            $array['content']['application/json']['schema']['$ref']
        );
    }

    /** @test */
    public function response_ok_factory_creates_200_response(): void
    {
        $response = Response::ok(description: 'All good');

        $array = $response->toArray();

        $this->assertEquals(200, $response->status);
        $this->assertEquals('All good', $array['description']);
    }

    /** @test */
    public function response_created_factory_creates_201_response(): void
    {
        $response = Response::created();

        $this->assertEquals(201, $response->status);
    }

    /** @test */
    public function response_not_found_factory_creates_404_response(): void
    {
        $response = Response::notFound();

        $this->assertEquals(404, $response->status);
    }

    /** @test */
    public function response_validation_error_factory_creates_422_response(): void
    {
        $response = Response::validationError();

        $this->assertEquals(422, $response->status);
    }
}
