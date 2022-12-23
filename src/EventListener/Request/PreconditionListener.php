<?php

declare(strict_types=1);

namespace Guhemama\HttpPreconditionBundle\EventListener\Request;

use Guhemama\HttpPreconditionBundle\Annotations\Precondition;
use Guhemama\HttpPreconditionBundle\Exception\Http\PreconditionFailedHttpException;
use ReflectionMethod;
use ReflectionException;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;

use function count;
use function is_array;
use function is_callable;
use function is_object;

final class PreconditionListener
{
    public function __construct(
        private readonly ExpressionLanguage $expressionLanguage
    ) {
    }

    /**
     * @throws ReflectionException
     */
    public function __invoke(ControllerArgumentsEvent $event): void
    {
        /** @var string|string[]|object $controller */
        $controller = $event->getController();

        if (is_array($controller)) {
            $reflectedMethod = new ReflectionMethod($controller[0], $controller[1]);
        } elseif (is_object($controller) && is_callable([$controller, '__invoke'])) {
            $reflectedMethod = new ReflectionMethod($controller, '__invoke');
        } else {
            return;
        }

        $attributes = $reflectedMethod->getAttributes(Precondition::class);
        if (count($attributes) === 0) {
            return;
        }

        // Symfony 6+
        if (method_exists($event, 'getNamedArguments')) {
            $values = $event->getNamedArguments();
        // Symfony >6
        } else {
            $values = $event->getRequest()->attributes->all();
        }

        foreach ($attributes as $attribute) {
            /** @var Precondition $precondition */
            $precondition = $attribute->newInstance();
            $result = $this->expressionLanguage->evaluate($precondition->expr, $values);

            if (false === $result) {
                throw new PreconditionFailedHttpException($precondition);
            }
        }
    }
}