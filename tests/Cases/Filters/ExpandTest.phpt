<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Contributte\Tester\Toolkit;
use Tester\Assert;
use Varhall\Restino\Filters\Expand;
use Varhall\Restino\Results\Result;
use Varhall\Restino\Controllers\RestRequest;
use Tests\Fixtures\Utils;
use Nette\Http\IResponse;
use Varhall\Restino\Filters\Context;
use Nette\DI\Container;


Toolkit::test(function (): void {
    $rules = [
        'foo',
        'bar'   => 'xxx',
        'baz'   => fn($item) => 'bazvalue'
    ];

    $filter = new Expand($rules);

    Assert::equal([
        'foo' => 'foo',
        'bar' => 'xxx',
        'baz' => $rules['baz']
    ], $filter->getRules());
}, 'testConstructor');


Toolkit::test(function (): void {
    $rules = [
        'foo',
        'bar'   => 'xxx',
        'baz'   => fn($item) => 'bazvalue'
    ];

    $request = Utils::prepareRequest( [ '_expand' => 'foo,bar,baz' ]);
    $next = function(Context $context) {
        return new Result(new class() {
            public $name = 'John';
            public $surname = 'Smith';

            public function foo()
            {
                return 'foovalue';
            }

            public function xxx()
            {
                return 'barvalue';
            }
        });
    };

    $context = new Context(mock(Container::class), $request);

    $filter = new Expand($rules);
    $result = $filter->execute($context, $next);

    $output = $result->execute(mock(IResponse::class));

    Assert::equal([
        'name' => 'John',
        'surname' => 'Smith',
        'foo' => 'foovalue',
        'bar' => 'barvalue',
        'baz' => 'bazvalue'
    ], $output);
}, 'testExecute');
