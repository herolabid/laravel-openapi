<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Tests\Integration;

use Mockery;
use Psr\Log\NullLogger;
use HerolabID\LaravelOpenApi\Builders\ParameterBuilder;
use HerolabID\LaravelOpenApi\Builders\PathBuilder;
use HerolabID\LaravelOpenApi\Builders\ResponseBuilder;
use HerolabID\LaravelOpenApi\Builders\SchemaBuilder;
use HerolabID\LaravelOpenApi\Builders\SecurityBuilder;
use HerolabID\LaravelOpenApi\Builders\SpecBuilder;
use HerolabID\LaravelOpenApi\Contracts\CacheInterface;
use HerolabID\LaravelOpenApi\Parser\AttributeParser;
use HerolabID\LaravelOpenApi\Parser\ModelAnalyzer;
use HerolabID\LaravelOpenApi\Parser\ReflectionHelper;
use HerolabID\LaravelOpenApi\Parser\RouteAnalyzer;
use HerolabID\LaravelOpenApi\Parser\TypeResolver;
use HerolabID\LaravelOpenApi\Services\SpecGenerationService;
use HerolabID\LaravelOpenApi\Services\ValidationService;
use HerolabID\LaravelOpenApi\Tests\Fixtures\TestController;
use HerolabID\LaravelOpenApi\Tests\Fixtures\TestUser;
use HerolabID\LaravelOpenApi\Tests\Support\HasOpenApiAssertions;
use HerolabID\LaravelOpenApi\Tests\TestCase;

class EndToEndGenerationTest extends TestCase
{
    use HasOpenApiAssertions;

    private SpecGenerationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup all dependencies
        $reflectionHelper = new ReflectionHelper();
        $typeResolver = new TypeResolver();

        $attributeParser = new AttributeParser($reflectionHelper, $typeResolver);
        $modelAnalyzer = new ModelAnalyzer($reflectionHelper, $typeResolver);

        $routeAnalyzer = Mockery::mock(RouteAnalyzer::class);
        $routeAnalyzer->shouldReceive('getControllerClasses')
            ->andReturn([TestController::class]);

        $specBuilder = new SpecBuilder(
            new PathBuilder(
                new ParameterBuilder(),
                new ResponseBuilder()
            ),
            new SchemaBuilder(),
            new SecurityBuilder()
        );

        $validator = new ValidationService();

        $cache = Mockery::mock(CacheInterface::class);
        $cache->shouldReceive('has')->andReturn(false);
        $cache->shouldReceive('needsRegeneration')->andReturn(true);
        $cache->shouldReceive('get')->andReturn(null);
        $cache->shouldReceive('put')->andReturn(null);

        $this->service = new SpecGenerationService(
            $attributeParser,
            $modelAnalyzer,
            $routeAnalyzer,
            $specBuilder,
            new SecurityBuilder(),
            $validator,
            $cache,
            new NullLogger()
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_generates_complete_openapi_spec_from_fixtures(): void
    {
        // Mock config
        config([
            'openapi' => [
                'info' => [
                    'title' => 'Test API',
                    'version' => '1.0.0',
                ],
                'scan' => [
                    'controllers' => [TestController::class],
                    'models' => [TestUser::class],
                ],
                'cache' => [
                    'enabled' => false,
                ],
                'security' => [
                    'auto_detect' => false,
                    'schemes' => [],
                ],
            ],
        ]);

        // Generate spec
        $spec = $this->service->generate(['validate' => false]);

        // Assert basic structure
        $this->assertValidOpenApiSpec($spec);

        // Assert info
        $this->assertEquals('Test API', $spec['info']['title']);
        $this->assertEquals('1.0.0', $spec['info']['version']);

        // Assert has paths
        $this->assertArrayHasKey('paths', $spec);
        $this->assertNotEmpty($spec['paths']);

        // Assert specific paths from TestController
        $this->assertArrayHasKey('/api/users', $spec['paths']);
        $this->assertArrayHasKey('/api/users/{id}', $spec['paths']);

        // Assert operations
        $this->assertArrayHasKey('get', $spec['paths']['/api/users']);
        $this->assertArrayHasKey('post', $spec['paths']['/api/users']);

        // Assert has components
        $this->assertArrayHasKey('components', $spec);
    }

    /** @test */
    public function generated_spec_is_valid(): void
    {
        config([
            'openapi' => [
                'info' => ['title' => 'Test API', 'version' => '1.0.0'],
                'scan' => [
                    'controllers' => [TestController::class],
                    'models' => [TestUser::class],
                ],
                'cache' => ['enabled' => false],
                'security' => ['auto_detect' => false, 'schemes' => []],
            ],
        ]);

        // This should not throw ValidationException
        $spec = $this->service->generate();

        $this->assertValidOpenApiSpec($spec);
    }

    /** @test */
    public function it_includes_all_metadata_from_attributes(): void
    {
        config([
            'openapi' => [
                'info' => ['title' => 'Test API', 'version' => '1.0.0'],
                'scan' => [
                    'controllers' => [TestController::class],
                    'models' => [TestUser::class],
                ],
                'cache' => ['enabled' => false],
                'security' => ['auto_detect' => false, 'schemes' => []],
            ],
        ]);

        $spec = $this->service->generate(['validate' => false]);

        // Check GET /api/users
        $operation = $spec['paths']['/api/users']['get'];
        $this->assertEquals('List all users', $operation['summary']);
        $this->assertContains('Users', $operation['tags']);

        // Check parameters
        $this->assertArrayHasKey('parameters', $operation);
        $this->assertCount(2, $operation['parameters']);

        // Check responses
        $this->assertArrayHasKey('responses', $operation);
        $this->assertArrayHasKey('200', $operation['responses']);
    }
}
