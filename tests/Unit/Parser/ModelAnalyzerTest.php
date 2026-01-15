<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Tests\Unit\Parser;

use PHPUnit\Framework\TestCase;
use HerolabID\LaravelOpenApi\Parser\ModelAnalyzer;
use HerolabID\LaravelOpenApi\Parser\ReflectionHelper;
use HerolabID\LaravelOpenApi\Parser\TypeResolver;
use HerolabID\LaravelOpenApi\Tests\Fixtures\TestUser;

class ModelAnalyzerTest extends TestCase
{
    private ModelAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->analyzer = new ModelAnalyzer(
            new ReflectionHelper(),
            new TypeResolver()
        );
    }

    /** @test */
    public function it_analyzes_model_class(): void
    {
        $result = $this->analyzer->analyze(TestUser::class);

        $this->assertArrayHasKey('User', $result);
    }

    /** @test */
    public function it_extracts_schema_attribute(): void
    {
        $result = $this->analyzer->analyze(TestUser::class);

        $schema = $result['User'];

        $this->assertEquals('User', $schema['title']);
        $this->assertEquals('User model', $schema['description']);
    }

    /** @test */
    public function it_extracts_properties(): void
    {
        $result = $this->analyzer->analyze(TestUser::class);

        $schema = $result['User'];

        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('id', $schema['properties']);
        $this->assertArrayHasKey('name', $schema['properties']);
        $this->assertArrayHasKey('email', $schema['properties']);
    }

    /** @test */
    public function it_extracts_property_types(): void
    {
        $result = $this->analyzer->analyze(TestUser::class);

        $properties = $result['User']['properties'];

        $this->assertEquals('integer', $properties['id']['type']);
        $this->assertEquals('string', $properties['name']['type']);
        $this->assertEquals('email', $properties['email']['format']);
    }

    /** @test */
    public function it_extracts_property_constraints(): void
    {
        $result = $this->analyzer->analyze(TestUser::class);

        $nameProperty = $result['User']['properties']['name'];

        $this->assertEquals(3, $nameProperty['minLength']);
        $this->assertEquals(100, $nameProperty['maxLength']);
    }

    /** @test */
    public function it_marks_read_only_properties(): void
    {
        $result = $this->analyzer->analyze(TestUser::class);

        $idProperty = $result['User']['properties']['id'];

        $this->assertTrue($idProperty['readOnly']);
    }

    /** @test */
    public function it_extracts_required_fields(): void
    {
        $result = $this->analyzer->analyze(TestUser::class);

        $schema = $result['User'];

        $this->assertContains('name', $schema['required']);
        $this->assertContains('email', $schema['required']);
    }

    /** @test */
    public function it_handles_nullable_properties(): void
    {
        $result = $this->analyzer->analyze(TestUser::class);

        $ageProperty = $result['User']['properties']['age'];

        $this->assertTrue($ageProperty['nullable']);
    }
}
