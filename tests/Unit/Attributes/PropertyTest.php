<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Tests\Unit\Attributes;

use PHPUnit\Framework\TestCase;
use HerolabID\LaravelOpenApi\Attributes\Property;
use HerolabID\LaravelOpenApi\Attributes\Schema;

class PropertyTest extends TestCase
{
    /** @test */
    public function property_converts_to_array_with_basic_properties(): void
    {
        $property = new Property(
            type: 'string',
            description: 'User name',
            example: 'John Doe',
        );

        $array = $property->toArray();

        $this->assertEquals('string', $array['type']);
        $this->assertEquals('User name', $array['description']);
        $this->assertEquals('John Doe', $array['example']);
    }

    /** @test */
    public function property_includes_format(): void
    {
        $property = new Property(
            type: 'string',
            format: 'email',
        );

        $array = $property->toArray();

        $this->assertEquals('email', $array['format']);
    }

    /** @test */
    public function property_includes_validation_constraints(): void
    {
        $property = new Property(
            type: 'string',
            minLength: 3,
            maxLength: 50,
            pattern: '^[a-zA-Z]+$',
        );

        $array = $property->toArray();

        $this->assertEquals(3, $array['minLength']);
        $this->assertEquals(50, $array['maxLength']);
        $this->assertEquals('^[a-zA-Z]+$', $array['pattern']);
    }

    /** @test */
    public function property_includes_numeric_constraints(): void
    {
        $property = new Property(
            type: 'integer',
            minimum: 0,
            maximum: 100,
        );

        $array = $property->toArray();

        $this->assertEquals(0, $array['minimum']);
        $this->assertEquals(100, $array['maximum']);
    }

    /** @test */
    public function property_can_be_nullable(): void
    {
        $property = new Property(
            type: 'string',
            nullable: true,
        );

        $array = $property->toArray();

        $this->assertTrue($array['nullable']);
    }

    /** @test */
    public function property_can_be_read_only(): void
    {
        $property = new Property(
            type: 'integer',
            readOnly: true,
        );

        $array = $property->toArray();

        $this->assertTrue($array['readOnly']);
    }

    /** @test */
    public function property_array_includes_items_schema(): void
    {
        $property = new Property(
            type: 'array',
            items: new Schema(ref: 'User'),
        );

        $array = $property->toArray();

        $this->assertEquals('array', $array['type']);
        $this->assertEquals('#/components/schemas/User', $array['items']['$ref']);
    }
}
