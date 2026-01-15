<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Tests\Unit\Builders;

use PHPUnit\Framework\TestCase;
use HerolabID\LaravelOpenApi\Builders\SchemaBuilder;

class SchemaBuilderTest extends TestCase
{
    private SchemaBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new SchemaBuilder();
    }

    /** @test */
    public function it_builds_schema_with_properties(): void
    {
        $input = [
            'User' => [
                'type' => 'object',
                'title' => 'User',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'email' => ['type' => 'string', 'format' => 'email'],
                ],
                'required' => ['name', 'email'],
            ],
        ];

        $schemas = $this->builder->build($input);

        $this->assertArrayHasKey('User', $schemas);
        $this->assertEquals('object', $schemas['User']['type']);
        $this->assertArrayHasKey('properties', $schemas['User']);
        $this->assertCount(3, $schemas['User']['properties']);
        $this->assertEquals(['name', 'email'], $schemas['User']['required']);
    }

    /** @test */
    public function it_handles_property_constraints(): void
    {
        $input = [
            'User' => [
                'properties' => [
                    'age' => [
                        'type' => 'integer',
                        'minimum' => 0,
                        'maximum' => 150,
                    ],
                    'name' => [
                        'type' => 'string',
                        'minLength' => 3,
                        'maxLength' => 50,
                    ],
                ],
            ],
        ];

        $schemas = $this->builder->build($input);

        $age = $schemas['User']['properties']['age'];
        $this->assertEquals(0, $age['minimum']);
        $this->assertEquals(150, $age['maximum']);

        $name = $schemas['User']['properties']['name'];
        $this->assertEquals(3, $name['minLength']);
        $this->assertEquals(50, $name['maxLength']);
    }

    /** @test */
    public function it_handles_nullable_properties(): void
    {
        $input = [
            'User' => [
                'properties' => [
                    'age' => [
                        'type' => 'integer',
                        'nullable' => true,
                    ],
                ],
            ],
        ];

        $schemas = $this->builder->build($input);

        $this->assertTrue($schemas['User']['properties']['age']['nullable']);
    }

    /** @test */
    public function it_handles_read_only_properties(): void
    {
        $input = [
            'User' => [
                'properties' => [
                    'id' => [
                        'type' => 'integer',
                        'readOnly' => true,
                    ],
                ],
            ],
        ];

        $schemas = $this->builder->build($input);

        $this->assertTrue($schemas['User']['properties']['id']['readOnly']);
    }

    /** @test */
    public function it_merges_multiple_schema_collections(): void
    {
        $collection1 = ['User' => ['type' => 'object']];
        $collection2 = ['Post' => ['type' => 'object']];

        $merged = $this->builder->merge([$collection1, $collection2]);

        $this->assertArrayHasKey('User', $merged);
        $this->assertArrayHasKey('Post', $merged);
    }

    /** @test */
    public function it_returns_empty_array_for_empty_input(): void
    {
        $schemas = $this->builder->build([]);

        $this->assertEmpty($schemas);
    }
}
