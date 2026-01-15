<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Tests\Unit\Attributes;

use PHPUnit\Framework\TestCase;
use HerolabID\LaravelOpenApi\Attributes\Schema;

class SchemaTest extends TestCase
{
    /** @test */
    public function schema_converts_to_array_with_all_properties(): void
    {
        $schema = new Schema(
            title: 'User',
            description: 'User model',
            type: 'object',
            required: ['name', 'email'],
            properties: [
                'name' => ['type' => 'string'],
                'email' => ['type' => 'string', 'format' => 'email'],
            ],
            example: ['name' => 'John', 'email' => 'john@example.com'],
        );

        $array = $schema->toArray();

        $this->assertEquals('User', $array['title']);
        $this->assertEquals('User model', $array['description']);
        $this->assertEquals('object', $array['type']);
        $this->assertEquals(['name', 'email'], $array['required']);
        $this->assertArrayHasKey('name', $array['properties']);
        $this->assertArrayHasKey('email', $array['properties']);
    }

    /** @test */
    public function schema_ref_converts_to_reference(): void
    {
        $schema = Schema::ref('User');

        $array = $schema->toArray();

        $this->assertEquals('#/components/schemas/User', $array['$ref']);
    }

    /** @test */
    public function schema_array_factory_works_correctly(): void
    {
        $schema = Schema::array(
            items: new Schema(ref: 'User'),
            description: 'List of users',
        );

        $array = $schema->toArray();

        $this->assertEquals('array', $array['type']);
        $this->assertEquals('List of users', $array['description']);
        $this->assertArrayHasKey('items', $array);
        $this->assertEquals('#/components/schemas/User', $array['items']['$ref']);
    }

    /** @test */
    public function schema_object_factory_works_correctly(): void
    {
        $schema = Schema::object(
            properties: [
                'name' => ['type' => 'string'],
                'age' => ['type' => 'integer'],
            ],
            required: ['name'],
            description: 'Person object',
        );

        $array = $schema->toArray();

        $this->assertEquals('object', $array['type']);
        $this->assertEquals('Person object', $array['description']);
        $this->assertEquals(['name'], $array['required']);
        $this->assertArrayHasKey('name', $array['properties']);
        $this->assertArrayHasKey('age', $array['properties']);
    }
}
