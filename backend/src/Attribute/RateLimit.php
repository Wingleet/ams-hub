<?php

namespace App\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class RateLimit
{
    public function __construct(
        public string $limiter = 'api_limiter',
        public ?string $key = null,
    ) {}
}