<?php

declare(strict_types=1);

namespace Guhemama\HttpPreconditionBundle\Tests\Unit\EventListener\Request;

use Guhemama\HttpPreconditionBundle\Annotations\Precondition;
use Guhemama\HttpPreconditionBundle\EventListener\Request\PreconditionListener;
use Guhemama\HttpPreconditionBundle\Exception\Http\PreconditionFailedHttpException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class PreconditionListenerTest extends TestCase
{
    private PreconditionListener $listener;

    protected function setUp(): void
    {
        $this->listener = new PreconditionListener(new ExpressionLanguage());
    }

    public function testNoPreconditionThrowsNoException(): void
    {
        $event = $this->createControllerArgumentsEvent(new class {
            public function __invoke(): bool
            {
                return true;
            }
        });

        $this->listener->__invoke($event);
        self::assertTrue(true);
    }

    public function testSinglePreconditionThrowsNoException(): void
    {
        $event = $this->createControllerArgumentsEvent(new class {
            #[Precondition('1 + 1 == 2')]
            public function __invoke(): bool
            {
                return true;
            }
        });

        $this->listener->__invoke($event);
        self::assertTrue(true);
    }

    public function testSinglePreconditionThrowsException(): void
    {
        self::expectException(PreconditionFailedHttpException::class);
        self::expectExceptionMessage('A route precondition failed.');

        $event = $this->createControllerArgumentsEvent(new class {
            #[Precondition('1 + 1 == 3')]
            public function __invoke(): bool
            {
                return true;
            }
        });

        $this->listener->__invoke($event);
    }

    public function testSinglePreconditionThrowsExceptionWithCustomMessage(): void
    {
        self::expectException(PreconditionFailedHttpException::class);
        self::expectExceptionMessage('Cannot fix precision error.');

        $event = $this->createControllerArgumentsEvent(new class {
            #[Precondition('1.0 + 1.0 == 3.14', 'Cannot fix precision error.')]
            public function __invoke(): bool
            {
                return true;
            }
        });

        $this->listener->__invoke($event);
    }

    public function testMultiplePreconditionThrowsNoException(): void
    {
        $event = $this->createControllerArgumentsEvent(new class {
            #[Precondition('1 + 1 == 2', 'Checksum is invalid.')]
            #[Precondition("'apples' == 'apples'", 'Cannot compare apples to oranges.')]
            public function __invoke(): bool
            {
                return true;
            }
        });

        $this->listener->__invoke($event);
        self::assertTrue(true);
    }

    public function testMultiplePreconditionThrowsExceptionWhenSinglePreconditionFails(): void
    {
        self::expectException(PreconditionFailedHttpException::class);
        self::expectExceptionMessage('Cannot compare apples to oranges.');

        $event = $this->createControllerArgumentsEvent(new class {
            #[Precondition('1 + 1 == 2', 'Checksum is invalid.')]
            #[Precondition("'apples' == 'oranges'", 'Cannot compare apples to oranges.')]
            public function __invoke(): bool
            {
                return true;
            }
        });

        $this->listener->__invoke($event);
    }

    public function testPreconditionWithRegularControllerClassThrowsException(): void
    {
        self::expectException(PreconditionFailedHttpException::class);

        $controller = new class {
            #[Precondition("15 % 3 == 'fizz'")]
            public function createAction(): bool
            {
                return true;
            }
        };

        $event = $this->createControllerArgumentsEvent([$controller, 'createAction']);
        $this->listener->__invoke($event);
    }

    private function createControllerArgumentsEvent(callable $controller): ControllerArgumentsEvent
    {
        $kernelMock  = $this->createMock(HttpKernelInterface::class);
        $requestMock = $this->createMock(Request::class);

        return new ControllerArgumentsEvent($kernelMock, $controller, [], $requestMock, HttpKernelInterface::MAIN_REQUEST);
    }
}
