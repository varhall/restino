<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Varhall\Restino\Filters\Expand;
use Varhall\Restino\Controllers\RestRequest;
use Varhall\Restino\Results\IExpandable;
use Varhall\Restino\Filters\Context;
use Varhall\Restino\Results\CollectionResult;
use Varhall\Restino\Results\IResult;


/// requestedRules

Toolkit::test(function (): void {
    $request = mock(RestRequest::class);
    $request->shouldReceive('getParameter')
        ->with(Expand::QUERY_PARAMETER, '')
        ->andReturn('profile , alt ,details');

    $expand = new Expand();
    $rules = $expand->requestedRules($request);

    Assert::same(['profile', 'alt', 'details'], $rules);
}, 'requestedRules trims and splits comma-separated string');

Toolkit::test(function (): void {
    $request = mock(RestRequest::class);
    $request->shouldReceive('getParameter')
        ->with(Expand::QUERY_PARAMETER, '')
        ->andReturn('  ');

    $expand = new Expand();
    $rules = $expand->requestedRules($request);

    Assert::same([''], $rules);
}, 'requestedRules returns empty string if input is only whitespace');

Toolkit::test(function (): void {
    $request = mock(RestRequest::class);
    $request->shouldReceive('getParameter')
        ->with(Expand::QUERY_PARAMETER, '')
        ->andReturn('');

    $expand = new Expand();
    $rules = $expand->requestedRules($request);

    Assert::same([''], $rules);
}, 'requestedRules returns array with empty string if input is empty');


/// expand

Toolkit::test(function (): void {
    $expand = new Expand();

    $object = new class {
        public function getName(): string {
            return 'tester';
        }
    };

    $result = $expand->expand($object, 'getName');
    Assert::same('tester', $result);
}, 'expand() calls method if string method name exists');

Toolkit::test(function (): void {
    $expand = new Expand();

    $object = new class {
        public string $name = 'tester';
    };

    $result = $expand->expand($object, fn($obj) => strtoupper($obj->name));
    Assert::same('TESTER', $result);
}, 'expand() executes callable rule');

Toolkit::test(function (): void {
    $expand = new Expand();

    $object = new class {};

    $result = $expand->expand($object, 'nonExistingMethod');
    Assert::same(null, $result);
}, 'expand() returns null if method does not exist');

Toolkit::test(function (): void {
    $expand = new Expand();

    $result = $expand->expand(null, 'anyMethod');
    Assert::same(null, $result);
}, 'expand() returns null if object is not an object');


/// map

Toolkit::test(function (): void {
    $expand = mock(Expand::class)->makePartial();
    $expand->shouldAllowMockingProtectedMethods();

    $object = mock(IExpandable::class);
    $object->shouldReceive('expansions')->andReturn([
        'author' => 'getAuthor',
        'details' => function ($obj) {
            return $obj->getDetails();
        },
    ]);

    $object->name = 'book';
    $object->shouldReceive('getAuthor')->andReturn('Tester');
    $object->shouldReceive('getDetails')->andReturn(['pages' => 100]);

    // mock expand() behavior
    $expand->shouldReceive('expand')->with($object, 'getAuthor')->andReturn('Tester');
    $expand->shouldReceive('expand')->with($object, Mockery::type('callable'))->andReturn(['pages' => 100]);

    $result = $expand->map($object, ['author', 'details']);

    Assert::same([
        'name' => 'book',
        'author' => 'Tester',
        'details' => ['pages' => 100],
    ], $result);
}, 'map() returns serialized + expanded fields from rules');

Toolkit::test(function (): void {
    $expand = mock(Expand::class)->makePartial();
    $expand->shouldAllowMockingProtectedMethods();

    $object = mock(IExpandable::class);
    $object->shouldReceive('expansions')->andReturn([
        'title', // simplified rule ['title'] should become ['title' => 'title']
    ]);
    $object->title = 'My Book';

    $expand->shouldReceive('expand')->with($object, 'title')->andReturn('My Book');

    $result = $expand->map($object, ['title']);

    Assert::same([
        'title' => 'My Book',
    ], $result);
}, 'map() normalizes simplified rules with string keys');

Toolkit::test(function (): void {
    $expand = mock(Expand::class)->makePartial();
    $expand->shouldAllowMockingProtectedMethods();

    $object = mock(IExpandable::class);
    $object->shouldReceive('expansions')->andReturn([
        'author' => 'getAuthor',
    ]);

    $object->name = 'book';

    $result = $expand->map($object, []); // no query

    Assert::same(['name' => 'book'], $result);
}, 'map() returns only serialized object when no query');

Toolkit::test(function (): void {
    $expand = mock(Expand::class)->makePartial();
    $expand->shouldAllowMockingProtectedMethods();

    $object = mock(IExpandable::class);
    $object->shouldReceive('expansions')->andReturn([]);

    $object->foo = 'bar';

    $result = $expand->map($object, ['nonexistent']);
    Assert::same(['foo' => 'bar'], $result);
}, 'map() ignores queries not in expansion rules');


Toolkit::test(function (): void {
    $context = mock(Context::class);
    $request = mock(RestRequest::class);
    $result = mock(CollectionResult::class);

    $context->shouldReceive('getRequest')->once()->andReturn($request);

    $expand = mock(Expand::class)->makePartial();
    $expand->shouldAllowMockingProtectedMethods();

    $expand->shouldReceive('requestedRules')
        ->once()
        ->with($request)
        ->andReturn(['foo']);

    $expand->shouldReceive('map')
        ->once()
        ->with(Mockery::type(IExpandable::class), ['foo'])
        ->andReturn(['mapped' => true]);

    // Ověříme, že mapper přetvoří jen IExpandable
    $result->shouldReceive('addMapper')->once()->with(Mockery::on(function ($mapper) use ($expand) {
        $nonExpandable = new class {};
        $expandable = new class implements IExpandable {
            public function expansions(): array {
                return ['foo' => 'getFoo'];
            }
        };

        $mapped = $mapper($expandable);
        $same = $mapper($nonExpandable);

        return is_array($mapped) && $same === $nonExpandable;
    }));

    $returned = $expand->execute($context, fn() => $result);

    Assert::same($result, $returned);
}, 'execute() adds mapper and maps IExpandable');

Toolkit::test(function (): void {
    $context = mock(Context::class);
    $expand = new Expand();

    $nonMappedResult = mock(IResult::class); // není AbstractResult, mapper se nepřidá

    $returned = $expand->execute($context, fn() => $nonMappedResult);

    Assert::same($nonMappedResult, $returned);
}, 'execute() skips non-AbstractResult values');