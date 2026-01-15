<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Attributes\Operation;

use Attribute;
use HerolabID\LaravelOpenApi\Attributes\Operation;

/**
 * PATCH operation attribute.
 */
#[Attribute(Attribute::TARGET_METHOD)]
class Patch extends Operation
{
    public function getMethod(): string
    {
        return 'patch';
    }
}
