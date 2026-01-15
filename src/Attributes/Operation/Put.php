<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Attributes\Operation;

use Attribute;
use HerolabID\LaravelOpenApi\Attributes\Operation;

/**
 * PUT operation attribute.
 */
#[Attribute(Attribute::TARGET_METHOD)]
class Put extends Operation
{
    public function getMethod(): string
    {
        return 'put';
    }
}
