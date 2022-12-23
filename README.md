[![Packagist Version](https://img.shields.io/packagist/v/guhemama/http-precondition-bundle)](https://packagist.org/packages/guhemama/http-precondition-bundle)
[![Packagist Downloads](https://img.shields.io/packagist/dm/guhemama/http-precondition-bundle)](https://packagist.org/packages/guhemama/http-precondition-bundle)

HTTP Precondition Bundle
================================
This bundle introduces a `Precondition` attribute that can be used
to check for certain conditions when routing. When the conditions are
not met, an exception is thrown (`412 Precondition failed`).

Installation
------------

Install the bundle with Composer: 
```
$ composer require guhemama/http-precondition-bundle
```

Usage
-----

To define a new precondition, import the `Guhemama\HttpPreconditionBundle\Annotations\Precondition`
attribute and provide an expression `expr` to be evaluated - any valid ExpressionLanguage expression is accepted.

```php
<?php

namespace App\Controller;

use Guhemama\HttpPreconditionBundle\Annotations\Precondition;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class QuestionController extends AbstractController
{
    #[Precondition(expr: "1+1 > 2")]
    #[Route('/question')]
    public function index(): JsonResponse
    {
        return $this->json(['answer' => 42]);
    }
}
```

When using the `ParamConverter` (Symfony 5) or the `MapEntity` (Symfony 6+) attributes,
you can also refer to the mapped entities in the precondition expression:

```php
<?php

use App\Entity\Question;
use Guhemama\HttpPreconditionBundle\Annotations\Precondition;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;

class QuestionController extends AbstractController
{
    #[Precondition(expr: '!question.isAnswered()', message: 'Cannot answer an already answered question.', payload: ['error' => 'QUESTION_ALREADY_ANSWERED'])]
    #[Route(path: '/question/{id}', methods: ['POST'])]
    public function update(
        #[MapEntity(Question::class)] Question $question
    ): Response
    {
        return new JsonResponse($question);
    }
}
```

When the precondition expression evaluates to false, an `\Guhemama\HttpPreconditionBundle\Exception\Http\PreconditionFailedHttpException` exception is thrown.
This exception also includes an instance of the `Precondition` should you need access to its configured values (e.g. `payload`). 

Configuration
-------------

This bundle depends on the [ExpressionLanguage component](https://github.com/symfony/expression-language).
If you have extended the expression language or would like to use
a another instance of it instead of the default one, you can decorate 
the service we inject in the precondition listener:

```yaml
services:
  App\ExpressionLanguage\MyCustomLanguage:
    decorates: guhemama.http_precondition.expression_language
```