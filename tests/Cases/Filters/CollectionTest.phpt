<?php

declare(strict_types=1);

use Contributte\Tester\Toolkit;
use Tester\Assert;
use Varhall\Restino\Filters\Collection;
use Varhall\Restino\Results\CollectionResult;
use Varhall\Restino\Results\IResult;
use Varhall\Restino\Results\Result;
use Varhall\Utilino\Collections\ArrayCollection;
use Tests\Fixtures\Utils;
use Varhall\Restino\Filters\Context;
use Nette\DI\Container;

require __DIR__ . '/../../bootstrap.php';

Toolkit::test(function (): void {
    $filter = new Collection();
    $request = Utils::prepareRequest([ '_limit'=> '3', '_offset' => 2 ]);
    $context = new Context(mock(Container::class), $request);

    $processedResult = $filter->execute($context, fn($request) => new Result(ArrayCollection::range(1, 10)));

    Assert::type(IResult::class, $processedResult);
    Assert::type(CollectionResult::class, $processedResult);
}, 'testInvoke');


Toolkit::test(function (): void {
    $filter = new Collection();
    $request = Utils::prepareRequest([ '_limit'=> '3', '_offset' => 2 ]);
    $context = new Context(mock(Container::class), $request);

    $result = $filter->execute($context, fn($request) => new Result(ArrayCollection::range(1, 10)));

    Assert::equal(3, $result->getLimit());
    Assert::equal(2, $result->getOffset());
}, 'testPaginate');


Toolkit::test(function (): void {
    $filter = new Collection();
    $request = Utils::prepareRequest([ '_order' => 'foo,-bar' ]);
    $context = new Context(mock(Container::class), $request);

    $result = $filter->execute($context, fn($request) => new Result(ArrayCollection::range(1, 10)));

    Assert::equal(2, count($result->getOrder()));
    Assert::equal([ 'foo' => false, 'bar' => true ], $result->getOrder());
}, 'testOrder');

