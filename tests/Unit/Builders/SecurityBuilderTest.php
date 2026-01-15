<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Tests\Unit\Builders;

use PHPUnit\Framework\TestCase;
use HerolabID\LaravelOpenApi\Builders\SecurityBuilder;

class SecurityBuilderTest extends TestCase
{
    private SecurityBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new SecurityBuilder();
    }

    /** @test */
    public function it_builds_http_bearer_scheme(): void
    {
        $input = [
            'bearer' => [
                'type' => 'http',
                'scheme' => 'bearer',
                'bearerFormat' => 'JWT',
                'description' => 'JWT authentication',
            ],
        ];

        $schemes = $this->builder->build($input);

        $this->assertArrayHasKey('bearer', $schemes);
        $this->assertEquals('http', $schemes['bearer']['type']);
        $this->assertEquals('bearer', $schemes['bearer']['scheme']);
        $this->assertEquals('JWT', $schemes['bearer']['bearerFormat']);
    }

    /** @test */
    public function it_builds_api_key_scheme(): void
    {
        $input = [
            'apiKey' => [
                'type' => 'apiKey',
                'name' => 'X-API-Key',
                'in' => 'header',
            ],
        ];

        $schemes = $this->builder->build($input);

        $this->assertEquals('apiKey', $schemes['apiKey']['type']);
        $this->assertEquals('X-API-Key', $schemes['apiKey']['name']);
        $this->assertEquals('header', $schemes['apiKey']['in']);
    }

    /** @test */
    public function it_builds_oauth2_scheme(): void
    {
        $input = [
            'oauth2' => [
                'type' => 'oauth2',
                'flows' => [
                    'password' => [
                        'tokenUrl' => '/oauth/token',
                        'scopes' => ['read', 'write'],
                    ],
                ],
            ],
        ];

        $schemes = $this->builder->build($input);

        $this->assertEquals('oauth2', $schemes['oauth2']['type']);
        $this->assertArrayHasKey('flows', $schemes['oauth2']);
    }

    /** @test */
    public function it_returns_empty_for_empty_input(): void
    {
        $schemes = $this->builder->build([]);

        $this->assertEmpty($schemes);
    }
}
