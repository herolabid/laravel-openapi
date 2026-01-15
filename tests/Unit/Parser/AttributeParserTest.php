<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Tests\Unit\Parser;

use PHPUnit\Framework\TestCase;
use HerolabID\LaravelOpenApi\Parser\AttributeParser;
use HerolabID\LaravelOpenApi\Parser\ReflectionHelper;
use HerolabID\LaravelOpenApi\Parser\TypeResolver;
use HerolabID\LaravelOpenApi\Tests\Fixtures\TestController;

class AttributeParserTest extends TestCase
{
    private AttributeParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new AttributeParser(
            new ReflectionHelper(),
            new TypeResolver()
        );
    }

    /** @test */
    public function it_parses_controller_class(): void
    {
        $result = $this->parser->parseClass(TestController::class);

        $this->assertArrayHasKey('paths', $result);
        $this->assertNotEmpty($result['paths']);
    }

    /** @test */
    public function it_parses_get_operation(): void
    {
        $result = $this->parser->parseClass(TestController::class);

        $this->assertArrayHasKey('/api/users', $result['paths']);
        $this->assertArrayHasKey('get', $result['paths']['/api/users']);
    }

    /** @test */
    public function it_parses_operation_summary(): void
    {
        $result = $this->parser->parseClass(TestController::class);

        $operation = $result['paths']['/api/users']['get'];

        $this->assertEquals('List all users', $operation['summary']);
        $this->assertEquals('Get a paginated list of users', $operation['description']);
    }

    /** @test */
    public function it_parses_parameters(): void
    {
        $result = $this->parser->parseClass(TestController::class);

        $operation = $result['paths']['/api/users']['get'];

        $this->assertArrayHasKey('parameters', $operation);
        $this->assertCount(2, $operation['parameters']);
        $this->assertEquals('page', $operation['parameters'][0]['name']);
        $this->assertEquals('query', $operation['parameters'][0]['in']);
    }

    /** @test */
    public function it_parses_path_parameters(): void
    {
        $result = $this->parser->parseClass(TestController::class);

        $operation = $result['paths']['/api/users/{id}']['get'];

        $this->assertArrayHasKey('parameters', $operation);
        $this->assertEquals('id', $operation['parameters'][0]['name']);
        $this->assertEquals('path', $operation['parameters'][0]['in']);
        $this->assertTrue($operation['parameters'][0]['required']);
    }

    /** @test */
    public function it_parses_request_body(): void
    {
        $result = $this->parser->parseClass(TestController::class);

        $operation = $result['paths']['/api/users']['post'];

        $this->assertArrayHasKey('requestBody', $operation);
        $this->assertTrue($operation['requestBody']['required']);
    }

    /** @test */
    public function it_parses_responses(): void
    {
        $result = $this->parser->parseClass(TestController::class);

        $operation = $result['paths']['/api/users/{id}']['get'];

        $this->assertArrayHasKey('responses', $operation);
        $this->assertArrayHasKey('200', $operation['responses']);
        $this->assertArrayHasKey('404', $operation['responses']);
    }

    /** @test */
    public function it_ignores_methods_without_attributes(): void
    {
        $result = $this->parser->parseClass(TestController::class);

        // Should only have 2 paths (index and show use same path)
        $this->assertCount(2, $result['paths']);
    }

    /** @test */
    public function it_parses_operation_id(): void
    {
        $result = $this->parser->parseClass(TestController::class);

        $operation = $result['paths']['/api/users/{id}']['get'];

        $this->assertEquals('getUserById', $operation['operationId']);
    }
}
