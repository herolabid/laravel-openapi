<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use HerolabID\LaravelOpenApi\Exceptions\ValidationException;
use HerolabID\LaravelOpenApi\Services\ValidationService;

class ValidationServiceTest extends TestCase
{
    private ValidationService $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ValidationService();
    }

    /** @test */
    public function it_validates_minimal_valid_spec(): void
    {
        $spec = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
            ],
            'paths' => [],
        ];

        $this->validator->validate($spec);

        $this->assertEmpty($this->validator->getErrors());
    }

    /** @test */
    public function it_throws_exception_for_missing_openapi_version(): void
    {
        $spec = [
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
            ],
        ];

        $this->expectException(ValidationException::class);
        $this->validator->validate($spec);
    }

    /** @test */
    public function it_throws_exception_for_missing_info(): void
    {
        $spec = [
            'openapi' => '3.1.0',
        ];

        $this->expectException(ValidationException::class);
        $this->validator->validate($spec);
    }

    /** @test */
    public function it_throws_exception_for_missing_info_title(): void
    {
        $spec = [
            'openapi' => '3.1.0',
            'info' => [
                'version' => '1.0.0',
            ],
        ];

        $this->expectException(ValidationException::class);
        $this->validator->validate($spec);
    }

    /** @test */
    public function it_validates_paths(): void
    {
        $spec = [
            'openapi' => '3.1.0',
            'info' => ['title' => 'API', 'version' => '1.0.0'],
            'paths' => [
                '/users' => [
                    'get' => [
                        'responses' => [
                            '200' => [
                                'description' => 'Success',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->validator->validate($spec);

        $this->assertEmpty($this->validator->getErrors());
    }

    /** @test */
    public function it_throws_exception_for_missing_responses(): void
    {
        $spec = [
            'openapi' => '3.1.0',
            'info' => ['title' => 'API', 'version' => '1.0.0'],
            'paths' => [
                '/users' => [
                    'get' => [],
                ],
            ],
        ];

        $this->expectException(ValidationException::class);
        $this->validator->validate($spec);
    }

    /** @test */
    public function it_validates_security_schemes(): void
    {
        $spec = [
            'openapi' => '3.1.0',
            'info' => ['title' => 'API', 'version' => '1.0.0'],
            'components' => [
                'securitySchemes' => [
                    'bearer' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                    ],
                ],
            ],
        ];

        $this->validator->validate($spec);

        $this->assertEmpty($this->validator->getErrors());
    }

    /** @test */
    public function it_collects_warnings(): void
    {
        $spec = [
            'openapi' => '3.1.0',
            'info' => ['title' => 'API', 'version' => '1.0.0'],
            'paths' => [],
        ];

        $this->validator->validate($spec);

        $warnings = $this->validator->getWarnings();
        $this->assertNotEmpty($warnings);
        $this->assertStringContainsString('No paths defined', $warnings[0]);
    }

    /** @test */
    public function it_checks_if_spec_is_valid(): void
    {
        $validSpec = [
            'openapi' => '3.1.0',
            'info' => ['title' => 'API', 'version' => '1.0.0'],
        ];

        $invalidSpec = [
            'info' => ['title' => 'API'],
        ];

        $this->assertTrue($this->validator->isValid($validSpec));
        $this->assertFalse($this->validator->isValid($invalidSpec));
    }
}
