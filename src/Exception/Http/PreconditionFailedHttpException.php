<?php

declare(strict_types=1);

namespace Guhemama\HttpPreconditionBundle\Exception\Http;

use Guhemama\HttpPreconditionBundle\Annotations\Precondition;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException as BasePreconditionFailedHttpException;
use Throwable;

class PreconditionFailedHttpException extends BasePreconditionFailedHttpException
{
    /** @param array<string, mixed> $headers */
    public function __construct(
        public readonly Precondition $precondition,
        Throwable|null $previous = null,
        int $code = 0,
        array $headers = [],
    ) {
        $message = ! empty($precondition->message) ? $precondition->message : 'A route precondition failed.';

        parent::__construct($message, $previous, $code, $headers);
    }
}
