<?php

declare(strict_types=1);

namespace Guhemama\HttpPreconditionBundle\EventListener\Request;

use Guhemama\HttpPreconditionBundle\Annotations\Precondition;
use Guhemama\HttpPreconditionBundle\Exception\Http\PreconditionFailedHttpException;
use ReflectionException;
use ReflectionMethod;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;

use function assert;
use function count;
use function is_array;
use function is_callable;
use function is_object;
use function is_string;

final class PreconditionListener
{
    public function __construct(
        private readonly ExpressionLanguage $expressionLanguage,
    ) {
    }

    /** @throws ReflectionException */
    public function __invoke(ControllerArgumentsEvent $event): void
    {
        $handler = $event->getController();

        if (is_array($handler) && count($handler) >= 2) {
            [$controller, $method] = $handler;
            assert(is_string($controller));
            assert(is_string($method));
            $reflectedMethod = new ReflectionMethod($controller, $method);
        } elseif (is_object($handler) && is_callable([$handler, '__invoke'])) {
            $reflectedMethod = new ReflectionMethod($handler, '__invoke');
        } else {
            return;
        }

        $attributes = $reflectedMethod->getAttributes(Precondition::class);
        if (count($attributes) === 0) {
            return;
        }

        $values = $event->getNamedArguments();

        foreach ($attributes as $attribute) {
            $precondition = $attribute->newInstance();
            assert($precondition instanceof Precondition);
            $result = $this->expressionLanguage->evaluate($precondition->expr, $values);

            if ($result === false) {
                throw new PreconditionFailedHttpException($precondition);
            }
        }
    }
}
