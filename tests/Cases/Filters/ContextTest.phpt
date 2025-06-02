<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Varhall\Restino\Controllers\RestRequest;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\DI\Container;
use Varhall\Restino\Filters\Context;

Toolkit::test(function (): void {
    // Mocky
    $request = Mockery::mock(RestRequest::class);
    $httpRequest = Mockery::mock(IRequest::class);
    $httpResponse = Mockery::mock(IResponse::class);

    // Kontejner
    $container = Mockery::mock(Container::class);
    $container->shouldReceive('getByType')
        ->with(IRequest::class)
        ->andReturn($httpRequest);
    $container->shouldReceive('getByType')
        ->with(IResponse::class)
        ->andReturn($httpResponse);

    // Context
    $context = new Context($container, $request);

    // Testy
    Assert::same($container, $context->getContainer(), 'getContainer');
    Assert::same($request, $context->getRequest(), 'getRequest');
    Assert::same($httpRequest, $context->getHttpRequest(), 'getHttpRequest');
    Assert::same($httpResponse, $context->getHttpResponse(), 'getHttpResponse');
}, 'Context vrací správné komponenty z containeru');
