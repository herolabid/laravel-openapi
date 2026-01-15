<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Tests\Unit\Attributes;

use PHPUnit\Framework\TestCase;
use HerolabID\LaravelOpenApi\Attributes\SecurityScheme;

class SecuritySchemeTest extends TestCase
{
    /** @test */
    public function bearer_factory_creates_bearer_token_scheme(): void
    {
        $scheme = SecurityScheme::bearer();

        $array = $scheme->toArray();

        $this->assertEquals('http', $array['type']);
        $this->assertEquals('bearer', $array['scheme']);
        $this->assertEquals('JWT', $array['bearerFormat']);
    }

    /** @test */
    public function basic_factory_creates_basic_auth_scheme(): void
    {
        $scheme = SecurityScheme::basic();

        $array = $scheme->toArray();

        $this->assertEquals('http', $array['type']);
        $this->assertEquals('basic', $array['scheme']);
    }

    /** @test */
    public function api_key_factory_creates_api_key_scheme(): void
    {
        $scheme = SecurityScheme::apiKey(
            keyName: 'X-API-Key',
            in: 'header',
        );

        $array = $scheme->toArray();

        $this->assertEquals('apiKey', $array['type']);
        $this->assertEquals('header', $array['in']);
        $this->assertEquals('X-API-Key', $array['name']);
    }
}
