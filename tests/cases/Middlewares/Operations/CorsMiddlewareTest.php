<?php

namespace tests\cases\Middlewares\Operations;

use Nette\Http\IResponse;
use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Varhall\Restino\Controllers\RestRequest;
use Varhall\Restino\Middlewares\Operations\CorsMiddleware;
use Varhall\Restino\Results\Result;
use Varhall\Restino\Results\Termination;

require_once __DIR__ . '/../../../bootstrap.php';

class CorsMiddlewareTest extends BaseTestCase
{
    public function testExecute()
    {
        $response = mock(IResponse::class);
        $response->shouldReceive('setHeader')->with('Access-Control-Allow-Origin', '*')->once();
        $response->shouldReceive('setHeader')->with('Access-Control-Allow-Headers', 'Content-type, Authorization')->once();
        $response->shouldReceive('setHeader')->with('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE')->once();

        $request = $this->prepareRequest();
        $request->getRequest()->setMethod('OPTIONS');

        $middleware = new CorsMiddleware($response);

        $result = $middleware($request, fn($x) => new Result(1));

        Assert::type(Termination::class, $result);
    }

    public function testNext()
    {
        $request = $this->prepareRequest();
        $request->getRequest()->setMethod('GET');

        $middleware = new CorsMiddleware(mock(IResponse::class));

        $result = $middleware($request, fn($x) => new Result(1));

        Assert::type(Result::class, $result);
    }
}

(new CorsMiddlewareTest())->run();
