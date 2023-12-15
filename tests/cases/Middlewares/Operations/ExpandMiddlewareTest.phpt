<?php

namespace tests\cases\Middlewares\Operations;

use Nette\Http\IResponse;
use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Varhall\Restino\Controllers\RestRequest;
use Varhall\Restino\Middlewares\Operations\ExpandMiddleware;
use Varhall\Restino\Results\Result;

require_once __DIR__ . '/../../../bootstrap.php';

class ExpandMiddlewareTest extends BaseTestCase
{
    public function testConstructor()
    {
        $rules = [
            'foo',
            'bar'   => 'xxx',
            'baz'   => fn($item) => 'bazvalue'
        ];

        $middleware = new ExpandMiddleware($rules);

        Assert::equal([
            'foo' => 'foo',
            'bar' => 'xxx',
            'baz' => $rules['baz']
        ], $middleware->getRules());
    }

    public function testExecute()
    {
        $rules = [
            'foo',
            'bar'   => 'xxx',
            'baz'   => fn($item) => 'bazvalue'
        ];

        $request = $this->prepareRequest( [ '_expand' => 'foo,bar,baz' ]);
        $next = function(RestRequest $request) {
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

        $middleware = new ExpandMiddleware($rules);
        $result = $middleware($request, $next);

        $output = $result->execute(mock(IResponse::class));

        Assert::equal([
            'name' => 'John',
            'surname' => 'Smith',
            'foo' => 'foovalue',
            'bar' => 'barvalue',
            'baz' => 'bazvalue'
        ], $output);
    }
}

(new ExpandMiddlewareTest())->run();
