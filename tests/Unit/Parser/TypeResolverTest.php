<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Tests\Unit\Parser;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;
use HerolabID\LaravelOpenApi\Parser\TypeResolver;
use HerolabID\LaravelOpenApi\Tests\Fixtures\TestUser;

class TypeResolverTest extends TestCase
{
    private TypeResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new TypeResolver();
    }

    /** @test */
    public function it_resolves_int_type(): void
    {
        $property = new ReflectionProperty(TestUser::class, 'id');
        $type = $property->getType();

        $schema = $this->resolver->resolve($type);

        $this->assertEquals(['type' => 'integer'], $schema);
    }

    /** @test */
    public function it_resolves_string_type(): void
    {
        $property = new ReflectionProperty(TestUser::class, 'name');
        $type = $property->getType();

        $schema = $this->resolver->resolve($type);

        $this->assertEquals(['type' => 'string'], $schema);
    }

    /** @test */
    public function it_resolves_nullable_type(): void
    {
        $property = new ReflectionProperty(TestUser::class, 'age');
        $type = $property->getType();

        $schema = $this->resolver->resolve($type);

        $this->assertEquals(['type' => 'integer', 'nullable' => true], $schema);
    }

    /** @test */
    public function it_resolves_array_notation_from_doc_block(): void
    {
        $schema = $this->resolver->resolveFromDocBlock('string[]');

        $this->assertEquals('array', $schema['type']);
        $this->assertEquals(['type' => 'string'], $schema['items']);
    }

    /** @test */
    public function it_resolves_generic_array_notation(): void
    {
        $schema = $this->resolver->resolveFromDocBlock('array<int>');

        $this->assertEquals('array', $schema['type']);
        $this->assertEquals(['type' => 'integer'], $schema['items']);
    }

    /** @test */
    public function it_gets_format_for_types(): void
    {
        $this->assertEquals('float', $this->resolver->getFormat('float'));
        $this->assertEquals('date-time', $this->resolver->getFormat('DateTime'));
        $this->assertNull($this->resolver->getFormat('string'));
    }
}
