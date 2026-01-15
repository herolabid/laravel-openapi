<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Tests\Unit\Builders;

use PHPUnit\Framework\TestCase;
use HerolabID\LaravelOpenApi\Builders\SpecBuilder;
use HerolabID\LaravelOpenApi\Builders\PathBuilder;
use HerolabID\LaravelOpenApi\Builders\SchemaBuilder;
use HerolabID\LaravelOpenApi\Builders\SecurityBuilder;
use HerolabID\LaravelOpenApi\Builders\ParameterBuilder;
use HerolabID\LaravelOpenApi\Builders\ResponseBuilder;

class SpecBuilderTest extends TestCase
{
    private SpecBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = new SpecBuilder(
            new PathBuilder(
                new ParameterBuilder(),
                new ResponseBuilder()
            ),
            new SchemaBuilder(),
            new SecurityBuilder()
        );
    }

    /** @test */
    public function it_builds_minimal_spec(): void
    {
        $spec = $this->builder->build();

        $this->assertEquals('3.1.0', $spec['openapi']);
        $this->assertArrayHasKey('info', $spec);
        $this->assertArrayHasKey('title', $spec['info']);
        $this->assertArrayHasKey('version', $spec['info']);
    }

    /** @test */
    public function it_sets_info(): void
    {
        $this->builder->setInfo([
            'title' => 'My API',
            'version' => '2.0.0',
            'description' => 'API Description',
        ]);

        $spec = $this->builder->build();

        $this->assertEquals('My API', $spec['info']['title']);
        $this->assertEquals('2.0.0', $spec['info']['version']);
        $this->assertEquals('API Description', $spec['info']['description']);
    }

    /** @test */
    public function it_sets_servers(): void
    {
        $this->builder->setServers([
            ['url' => 'https://api.example.com', 'description' => 'Production'],
            ['url' => 'https://staging.example.com', 'description' => 'Staging'],
        ]);

        $spec = $this->builder->build();

        $this->assertArrayHasKey('servers', $spec);
        $this->assertCount(2, $spec['servers']);
        $this->assertEquals('https://api.example.com', $spec['servers'][0]['url']);
    }

    /** @test */
    public function it_adds_paths(): void
    {
        $this->builder->addPaths([
            '/users' => [
                'get' => [
                    'summary' => 'List users',
                    'responses' => [
                        '200' => ['description' => 'Success'],
                    ],
                ],
            ],
        ]);

        $spec = $this->builder->build();

        $this->assertArrayHasKey('paths', $spec);
        $this->assertArrayHasKey('/users', $spec['paths']);
        $this->assertArrayHasKey('get', $spec['paths']['/users']);
    }

    /** @test */
    public function it_adds_schemas(): void
    {
        $this->builder->addSchemas([
            'User' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                ],
            ],
        ]);

        $spec = $this->builder->build();

        $this->assertArrayHasKey('components', $spec);
        $this->assertArrayHasKey('schemas', $spec['components']);
        $this->assertArrayHasKey('User', $spec['components']['schemas']);
    }

    /** @test */
    public function it_validates_required_fields(): void
    {
        $this->assertTrue($this->builder->validate());

        $this->builder->reset();
        $this->builder->setInfo(['title' => 'API']);

        $this->assertTrue($this->builder->validate());
    }

    /** @test */
    public function it_chains_builder_methods(): void
    {
        $spec = $this->builder
            ->setInfo(['title' => 'Test API', 'version' => '1.0.0'])
            ->setServers([['url' => 'https://api.test']])
            ->build();

        $this->assertEquals('Test API', $spec['info']['title']);
        $this->assertArrayHasKey('servers', $spec);
    }

    /** @test */
    public function it_cleans_up_empty_sections(): void
    {
        $this->builder->reset();

        $spec = $this->builder->build();

        // Empty paths should be removed
        $this->assertArrayNotHasKey('paths', $spec);
    }
}
