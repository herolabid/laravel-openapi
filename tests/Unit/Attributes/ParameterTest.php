<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Tests\Unit\Attributes;

use PHPUnit\Framework\TestCase;
use HerolabID\LaravelOpenApi\Attributes\Parameter;

class ParameterTest extends TestCase
{
    /** @test */
    public function parameter_converts_to_array_with_all_properties(): void
    {
        $parameter = new Parameter(
            name: 'id',
            in: 'path',
            description: 'User ID',
            required: true,
            schema: ['type' => 'integer'],
            example: 123,
        );

        $array = $parameter->toArray();

        $this->assertEquals('id', $array['name']);
        $this->assertEquals('path', $array['in']);
        $this->assertEquals('User ID', $array['description']);
        $this->assertTrue($array['required']);
        $this->assertEquals(['type' => 'integer'], $array['schema']);
        $this->assertEquals(123, $array['example']);
    }

    /** @test */
    public function path_parameter_factory_creates_required_parameter(): void
    {
        $parameter = Parameter::path('id', ['type' => 'integer']);

        $array = $parameter->toArray();

        $this->assertEquals('id', $array['name']);
        $this->assertEquals('path', $array['in']);
        $this->assertTrue($array['required']);
    }

    /** @test */
    public function query_parameter_factory_creates_optional_parameter(): void
    {
        $parameter = Parameter::query('filter', ['type' => 'string']);

        $array = $parameter->toArray();

        $this->assertEquals('filter', $array['name']);
        $this->assertEquals('query', $array['in']);
        $this->assertArrayNotHasKey('required', $array);
    }

    /** @test */
    public function header_parameter_factory_works_correctly(): void
    {
        $parameter = Parameter::header('X-API-Key', ['type' => 'string'], true);

        $array = $parameter->toArray();

        $this->assertEquals('X-API-Key', $array['name']);
        $this->assertEquals('header', $array['in']);
        $this->assertTrue($array['required']);
    }
}
