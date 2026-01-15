<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Tests\Integration;

use HerolabID\LaravelOpenApi\Parser\AttributeParser;
use HerolabID\LaravelOpenApi\Parser\ModelAnalyzer;
use HerolabID\LaravelOpenApi\Parser\ReflectionHelper;
use HerolabID\LaravelOpenApi\Parser\TypeResolver;
use HerolabID\LaravelOpenApi\Tests\Fixtures\TestController;
use HerolabID\LaravelOpenApi\Tests\Fixtures\TestUser;
use HerolabID\LaravelOpenApi\Tests\TestCase;

class ParsingIntegrationTest extends TestCase
{
    private AttributeParser $attributeParser;
    private ModelAnalyzer $modelAnalyzer;

    protected function setUp(): void
    {
        parent::setUp();

        $reflectionHelper = new ReflectionHelper();
        $typeResolver = new TypeResolver();

        $this->attributeParser = new AttributeParser($reflectionHelper, $typeResolver);
        $this->modelAnalyzer = new ModelAnalyzer($reflectionHelper, $typeResolver);
    }

    /** @test */
    public function it_generates_complete_openapi_structure_from_controller_and_model(): void
    {
        // Parse controller
        $paths = $this->attributeParser->parseClass(TestController::class);

        // Parse model
        $schemas = $this->modelAnalyzer->analyze(TestUser::class);

        // Build complete spec
        $spec = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
            ],
            'paths' => $paths['paths'],
            'components' => [
                'schemas' => $schemas,
            ],
        ];

        // Validate structure
        $this->assertArrayHasKey('openapi', $spec);
        $this->assertArrayHasKey('info', $spec);
        $this->assertArrayHasKey('paths', $spec);
        $this->assertArrayHasKey('components', $spec);

        // Validate paths
        $this->assertArrayHasKey('/api/users', $spec['paths']);
        $this->assertArrayHasKey('/api/users/{id}', $spec['paths']);

        // Validate operations
        $this->assertArrayHasKey('get', $spec['paths']['/api/users']);
        $this->assertArrayHasKey('post', $spec['paths']['/api/users']);
        $this->assertArrayHasKey('get', $spec['paths']['/api/users/{id}']);

        // Validate schemas
        $this->assertArrayHasKey('schemas', $spec['components']);
        $this->assertArrayHasKey('User', $spec['components']['schemas']);

        // Validate schema properties
        $userSchema = $spec['components']['schemas']['User'];
        $this->assertEquals('object', $userSchema['type']);
        $this->assertArrayHasKey('properties', $userSchema);
        $this->assertArrayHasKey('id', $userSchema['properties']);
        $this->assertArrayHasKey('name', $userSchema['properties']);
        $this->assertArrayHasKey('email', $userSchema['properties']);
    }

    /** @test */
    public function it_correctly_links_schema_references(): void
    {
        $paths = $this->attributeParser->parseClass(TestController::class);
        $schemas = $this->modelAnalyzer->analyze(TestUser::class);

        // Check that GET /api/users/{id} references User schema
        $operation = $paths['paths']['/api/users/{id}']['get'];
        $response = $operation['responses']['200'];

        $this->assertArrayHasKey('content', $response);
        $this->assertArrayHasKey('application/json', $response['content']);
        $this->assertArrayHasKey('schema', $response['content']['application/json']);

        $schema = $response['content']['application/json']['schema'];
        $this->assertEquals('#/components/schemas/User', $schema['$ref']);

        // Ensure User schema exists
        $this->assertArrayHasKey('User', $schemas);
    }

    /** @test */
    public function it_handles_multiple_response_codes(): void
    {
        $paths = $this->attributeParser->parseClass(TestController::class);

        // Check GET /api/users/{id} has both 200 and 404
        $operation = $paths['paths']['/api/users/{id}']['get'];

        $this->assertArrayHasKey('200', $operation['responses']);
        $this->assertArrayHasKey('404', $operation['responses']);

        $this->assertEquals('User found', $operation['responses']['200']['description']);
        $this->assertEquals('User not found', $operation['responses']['404']['description']);
    }

    /** @test */
    public function it_handles_request_body_with_schema_reference(): void
    {
        $paths = $this->attributeParser->parseClass(TestController::class);

        // Check POST /api/users has request body
        $operation = $paths['paths']['/api/users']['post'];

        $this->assertArrayHasKey('requestBody', $operation);
        $this->assertTrue($operation['requestBody']['required']);
        $this->assertArrayHasKey('content', $operation['requestBody']);
        $this->assertArrayHasKey('application/json', $operation['requestBody']['content']);
    }

    /** @test */
    public function it_extracts_all_metadata_from_attributes(): void
    {
        $paths = $this->attributeParser->parseClass(TestController::class);

        $operation = $paths['paths']['/api/users']['get'];

        // Check all metadata
        $this->assertEquals('List all users', $operation['summary']);
        $this->assertEquals('Get a paginated list of users', $operation['description']);
        $this->assertContains('Users', $operation['tags']);

        // Check parameters
        $this->assertCount(2, $operation['parameters']);

        $pageParam = $operation['parameters'][0];
        $this->assertEquals('page', $pageParam['name']);
        $this->assertEquals('query', $pageParam['in']);
        $this->assertEquals('integer', $pageParam['schema']['type']);
    }
}
