<?php

declare(strict_types=1);

namespace Guhemama\HttpPreconditionBundle\Annotations;

use Attribute;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
final readonly class Precondition
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public string $expr,
        public string $message = '',
        public array $payload = [],
    ) {
    }
}
