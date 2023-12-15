<?php

namespace tests\cases\Middlewares\Operations;

use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Varhall\Restino\Controllers\RestRequest;
use Varhall\Restino\Middlewares\Operations\ClosureMiddleware;
use Varhall\Restino\Results\Result;

require_once __DIR__ . '/../../../bootstrap.php';

class ClosureMiddlewareTest extends BaseTestCase
{
    public function testExecute()
    {
        $func = function(RestRequest $request, callable $next) {
            return $next($request);
        };

        $middleware = new ClosureMiddleware($func);

        $result = $middleware(mock(RestRequest::class), fn($x) => new Result(1));

        Assert::equal(1, $result->getData());
    }
}

(new ClosureMiddlewareTest())->run();
