<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use HerolabID\LaravelOpenApi\Attributes\Schema;
use HerolabID\LaravelOpenApi\Attributes\Property;

#[Schema(
    title: 'User',
    description: 'User model',
    required: ['name', 'email']
)]
class TestUser extends Model
{
    #[Property(
        type: 'integer',
        description: 'User ID',
        example: 1,
        readOnly: true
    )]
    public int $id;

    #[Property(
        type: 'string',
        description: 'User name',
        example: 'John Doe',
        minLength: 3,
        maxLength: 100
    )]
    public string $name;

    #[Property(
        type: 'string',
        format: 'email',
        description: 'Email address',
        example: 'john@example.com'
    )]
    public string $email;

    #[Property(
        type: 'integer',
        description: 'User age',
        example: 30,
        minimum: 0,
        maximum: 150,
        nullable: true
    )]
    public ?int $age;

    #[Property(
        type: 'string',
        format: 'date-time',
        description: 'Account creation timestamp',
        readOnly: true
    )]
    public string $created_at;
}
