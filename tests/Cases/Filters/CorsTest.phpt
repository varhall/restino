<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Varhall\Restino\Filters\Cors;
use Varhall\Restino\Results\Result;
use Varhall\Restino\Results\Termination;
use Nette\Http\IResponse;
use Tests\Fixtures\Utils;
use Varhall\Restino\Filters\Context;
use Nette\DI\Container;


Toolkit::test(function (): void {
    $response = createResponse();

    $request = Utils::prepareRequest();
    $request->getRequest()->setMethod('OPTIONS');

    $container = mock(Container::class);
    $container->shouldReceive('getHttpResponse')->andReturn($response);
    $container->shouldReceive('getByType')->with(\Nette\Http\IResponse::class)->andReturn($response);

    $context = new Context($container, $request);

    $filter = new Cors();

    $result = $filter->execute($context, fn($x) => new Result(1));

    Assert::type(Termination::class, $result);
}, 'testExecute');


Toolkit::test(function (): void {
    $response = createResponse();

    $request = Utils::prepareRequest();
    $request->getRequest()->setMethod('GET');

    $container = mock(Container::class);
    $container->shouldReceive('getHttpResponse')->andReturn($response);
    $container->shouldReceive('getByType')->with(\Nette\Http\IResponse::class)->andReturn($response);

    $context = new Context($container, $request);

    $filter = new Cors();

    $result = $filter->execute($context, fn($x) => new Result(1));

    Assert::type(Result::class, $result);
}, 'testNext');


function createResponse(): IResponse
{
    $response = mock(IResponse::class);
    $response->shouldReceive('setHeader')->with('Access-Control-Allow-Origin', '*')->once();
    $response->shouldReceive('setHeader')->with('Access-Control-Allow-Headers', 'Content-type, Authorization')->once();
    $response->shouldReceive('setHeader')->with('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE')->once();

    return $response;
}

