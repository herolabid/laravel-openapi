<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Tests\Fixtures;

use HerolabID\LaravelOpenApi\Attributes\Operation\Get;
use HerolabID\LaravelOpenApi\Attributes\Operation\Post;
use HerolabID\LaravelOpenApi\Attributes\Parameter;
use HerolabID\LaravelOpenApi\Attributes\RequestBody;
use HerolabID\LaravelOpenApi\Attributes\Response;
use HerolabID\LaravelOpenApi\Attributes\Schema;
use HerolabID\LaravelOpenApi\Attributes\Tag;

#[Tag('Users', 'User management endpoints')]
class TestController
{
    #[Get(
        path: '/api/users',
        summary: 'List all users',
        description: 'Get a paginated list of users',
        tags: ['Users'],
    )]
    #[Parameter::query('page', ['type' => 'integer'], false, 'Page number')]
    #[Parameter::query('limit', ['type' => 'integer'], false, 'Items per page')]
    #[Response::ok(
        Schema::array(new Schema(ref: TestUser::class)),
        'List of users'
    )]
    public function index(): array
    {
        return [];
    }

    #[Get(
        path: '/api/users/{id}',
        summary: 'Get user by ID',
        operationId: 'getUserById',
        tags: ['Users'],
    )]
    #[Parameter::path('id', ['type' => 'integer'], 'User ID')]
    #[Response::ok(Schema::ref(TestUser::class), 'User found')]
    #[Response::notFound('User not found')]
    public function show(int $id): ?array
    {
        return null;
    }

    #[Post(
        path: '/api/users',
        summary: 'Create a new user',
        tags: ['Users'],
    )]
    #[RequestBody::json(Schema::ref(TestUser::class), true, 'User data')]
    #[Response::created(Schema::ref(TestUser::class), 'User created')]
    #[Response::validationError('Validation failed')]
    public function store(): array
    {
        return [];
    }

    // Method without attributes (should be ignored)
    public function methodWithoutAttributes(): void
    {
    }
}
