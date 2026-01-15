<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Tests\Unit\Attributes;

use PHPUnit\Framework\TestCase;
use HerolabID\LaravelOpenApi\Attributes\Operation\Get;
use HerolabID\LaravelOpenApi\Attributes\Operation\Post;
use HerolabID\LaravelOpenApi\Attributes\Operation\Put;
use HerolabID\LaravelOpenApi\Attributes\Operation\Patch;
use HerolabID\LaravelOpenApi\Attributes\Operation\Delete;

class OperationTest extends TestCase
{
    /** @test */
    public function get_operation_has_correct_method(): void
    {
        $operation = new Get(path: '/api/users');

        $this->assertEquals('get', $operation->getMethod());
    }

    /** @test */
    public function post_operation_has_correct_method(): void
    {
        $operation = new Post(path: '/api/users');

        $this->assertEquals('post', $operation->getMethod());
    }

    /** @test */
    public function operation_converts_to_array_with_all_properties(): void
    {
        $operation = new Get(
            path: '/api/users/{id}',
            summary: 'Get user',
            description: 'Get user by ID',
            operationId: 'getUser',
            tags: ['Users'],
            deprecated: true,
            security: [['bearer' => []]],
        );

        $array = $operation->toArray();

        $this->assertEquals('Get user', $array['summary']);
        $this->assertEquals('Get user by ID', $array['description']);
        $this->assertEquals('getUser', $array['operationId']);
        $this->assertEquals(['Users'], $array['tags']);
        $this->assertTrue($array['deprecated']);
        $this->assertEquals([['bearer' => []]], $array['security']);
    }

    /** @test */
    public function operation_converts_to_array_with_minimal_properties(): void
    {
        $operation = new Get(
            path: '/api/users',
            summary: 'List users',
        );

        $array = $operation->toArray();

        $this->assertEquals('List users', $array['summary']);
        $this->assertArrayNotHasKey('description', $array);
        $this->assertArrayNotHasKey('deprecated', $array);
    }

    /** @test */
    public function all_http_methods_have_unique_method_names(): void
    {
        $operations = [
            new Get(path: '/'),
            new Post(path: '/'),
            new Put(path: '/'),
            new Patch(path: '/'),
            new Delete(path: '/'),
        ];

        $methods = array_map(fn($op) => $op->getMethod(), $operations);

        $this->assertEquals(['get', 'post', 'put', 'patch', 'delete'], $methods);
    }
}
