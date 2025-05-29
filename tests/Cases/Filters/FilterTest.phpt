<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Contributte\Tester\Toolkit;
use Tester\Assert;
use Varhall\Restino\Controllers\RestRequest;
use Nette\Database\Table\Selection;
use Nette\Http\Request;
use Varhall\Restino\Filters\Collection;
use Varhall\Restino\Filters\Expand;
use Varhall\Restino\Results\IResult;
use Varhall\Restino\Filters\Filter;
use Varhall\Restino\Filters\Context;
use Nette\DI\Container;
use Tests\Fixtures\Utils;

/// Generic test

function filterTest(array $query, Selection $selection): void {
    $request = mock(Request::class);
    $request->shouldReceive('getQuery')->andReturn($query);
    $container = mock(Container::class);
    $container->shouldReceive('getByType')->with(\Nette\Http\IRequest::class)->andReturn($request);
    $context = new Context($container, mock(RestRequest::class));


    $result = Mockery::mock(IResult::class);
    $result->shouldReceive('getData')->andReturn($selection);


    $filter = new Filter();
    $output = $filter->execute($context, fn() => $result);


    Assert::same($result, $output);
}


/// Test cases

Toolkit::test(function (): void {
    $query = [
        'property' => 'value',
    ];

    $selection = Mockery::mock(Selection::class);
    $selection->shouldReceive('where')
        ->once()->with('property = ?', 'value');

    filterTest($query, $selection);
}, 'Filter: equal');


Toolkit::test(function (): void {
    $query = [
        'age>' => 50,
    ];

    $selection = Mockery::mock(Selection::class);
    $selection->shouldReceive('where')
        ->once()->with('age >= ?', 50);

    filterTest($query, $selection);
}, 'Filter: greater or equal');


Toolkit::test(function (): void {
    $query = [
        'age!' => 50,
    ];

    $selection = Mockery::mock(Selection::class);
    $selection->shouldReceive('where')
        ->once()->with('age <> ?', 50);

    filterTest($query, $selection);
}, 'Filter: greater or equal');


Toolkit::test(function (): void {
    $query = [
        'age' => 'value*',
    ];

    $selection = Mockery::mock(Selection::class);
    $selection->shouldReceive('where')
        ->once()->with('age LIKE ?', 'value%');

    filterTest($query, $selection);
}, 'Filter: greater or equal');


Toolkit::test(function (): void {
    $query = [
        'type' => 'foo,bar,baz',
    ];

    $selection = Mockery::mock(Selection::class);
    $selection->shouldReceive('where')
        ->once()->with('type', [ 'foo', 'bar', 'baz' ]);

    filterTest($query, $selection);
}, 'Filter: greater or equal');


Toolkit::test(function (): void {
    $query = [
        'created' => '2024-05-01',
    ];

    $selection = Mockery::mock(Selection::class);
    $selection->shouldReceive('where')
        ->once()->with('created >= ?', '2024-05-01');
    $selection->shouldReceive('where')
        ->once()->with('created < ?', '2024-05-02');

    filterTest($query, $selection);
}, 'Filter: date');


Toolkit::test(function (): void {
    $query = [
        'deleted' => 'null',
    ];

    $selection = Mockery::mock(Selection::class);
    $selection->shouldReceive('where')
        ->once()->with('deleted', null);

    filterTest($query, $selection);
}, 'Filter: null value');


Toolkit::test(function (): void {
    $query = [
        Expand::QUERY_PARAMETER   => 'value',
        Collection::QUERY_OFFSET  => 'value',
        Collection::QUERY_LIMIT   => 'value',
        Collection::QUERY_ORDER   => 'value',
    ];

    $selection = Mockery::mock(Selection::class);
    $selection->shouldNotHaveBeenCalled();

    filterTest($query, $selection);
}, 'Filter: excluded system parameter');

