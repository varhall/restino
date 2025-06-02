<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Ninjify\Nunjuck\Toolkit;
use Nette\Http\IResponse;
use Nette\Http\IRequest;
use Nette\Database\Table\Selection;
use Tester\Assert;
use Varhall\Restino\Results\CollectionResult;
use Varhall\Utilino\Collections\ArrayCollection;

/// Test functions

function response(int $limit, int $offset, int $total, ?int $next, ?int $previous): IResponse
{
    $response = mock(IResponse::class);
    $response->shouldReceive('setHeader')->times(1)->with('X-Limit', $limit)->andReturnSelf();
    $response->shouldReceive('setHeader')->times(2)->with('X-Offset', $offset)->andReturnSelf();
    $response->shouldReceive('setHeader')->times(3)->with('X-Total', $total)->andReturnSelf();
    $response->shouldReceive('setHeader')->times(4)->with('X-Next-Offset', $next ?? '')->andReturnSelf();
    $response->shouldReceive('setHeader')->times(5)->with('X-Previous-Offset', $previous ?? '')->andReturnSelf();

    return $response;
}


/// Test Cases

// 1st page
Toolkit::test(function (): void {
    $data = ArrayCollection::range(1, 10);

    $response = response(3, 2, 10, 5, null);

    $result = new CollectionResult($data);
    $result->paginate(3, 2);
    $test = $result->execute($response, mock(IRequest::class));

    Assert::equal([ 3, 4, 5 ], $test);
}, 'testPaginate_structure');

// 2nd page
Toolkit::test(function (): void {
    $data = ArrayCollection::range(1, 10);

    $response = response(3, 5, 10, 8, 2);

    $result = new CollectionResult($data);
    $result->paginate(3, 5);
    $test = $result->execute($response, mock(IRequest::class));

    Assert::equal([ 6, 7, 8 ], $test);
}, 'testPaginate_structure');

// 3rd page
Toolkit::test(function (): void {
    $data = ArrayCollection::range(1, 10);

    $response = response(3, 8, 10, null, 5);

    $result = new CollectionResult($data);
    $result->paginate(3, 8);
    $test = $result->execute($response, mock(IRequest::class));

    Assert::equal([ 9, 10 ], $test);
}, 'testPaginate_structure');



Toolkit::test(function (): void {
    $data = mock(Selection::class);
    $data->shouldReceive('order')->once()->with('foo ASC')->andReturnSelf();
    $data->shouldReceive('order')->once()->with('bar DESC')->andReturnSelf();
    $data->shouldReceive('limit')->with(CollectionResult::DEFAULT_LIMIT, CollectionResult::DEFAULT_OFFSET)->andReturnSelf();
    $data->shouldReceive('count')->andReturn(10);

    $result = new CollectionResult($data);
    $result->order('foo', false);
    $result->order('bar', true);

    $response = response(CollectionResult::DEFAULT_LIMIT, CollectionResult::DEFAULT_OFFSET, 10, null, null);

    $test = $result->execute($response, mock(IRequest::class));

    Assert::type('array', $test);
}, 'testOrder');


Toolkit::test(function (): void {
    $mapper = function($a, $b) {};
    $data = ArrayCollection::range(1, 10);

    $core = new CollectionResult($data);
    $core->addMapper($mapper);

    $result = CollectionResult::fromResult($core);

    Assert::same($data, $result->getData());
    Assert::equal([ $mapper ], $result->mappers);
}, 'testFromResult');

