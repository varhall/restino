<?php

namespace tests\cases\Middlewares\Operations;

use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Varhall\Restino\Middlewares\Operations\CollectionMiddleware;
use Varhall\Restino\Results\CollectionResult;
use Varhall\Restino\Results\IResult;
use Varhall\Restino\Results\Result;
use Varhall\Utilino\Collections\ArrayCollection;


require_once __DIR__ . '/../../../bootstrap.php';

class CollectionMiddlewareTest extends BaseTestCase
{
    private CollectionMiddleware $middleware;

    protected function setUp(): void
    {
        $this->middleware = new CollectionMiddleware();
    }

    public function testInvoke(): void
    {
        $request = $this->prepareRequest([ '_limit'=> '3', '_offset' => 2 ]);

        $processedResult = ($this->middleware)($request, function ($request) {
            return new Result(ArrayCollection::range(1, 10));
        });

        Assert::type(IResult::class, $processedResult);
        Assert::type(CollectionResult::class, $processedResult);
    }

    public function testPaginate(): void
    {
        $request = $this->prepareRequest([ '_limit'=> '3', '_offset' => 2 ]);

        $result = ($this->middleware)($request, function ($request) {
            return new Result(ArrayCollection::range(1, 10));
        });

        Assert::equal(3, $result->getLimit());
        Assert::equal(2, $result->getOffset());
    }

    public function testOrder(): void
    {
        $request = $this->prepareRequest([ '_order' => 'foo,-bar' ]);

        $result = ($this->middleware)($request, function ($request) {
            return new Result(ArrayCollection::range(1, 10));
        });

        Assert::equal(2, count($result->getOrder()));
        Assert::equal([ 'foo' => false, 'bar' => true ], $result->getOrder());
    }
}

(new CollectionMiddlewareTest())->run();
