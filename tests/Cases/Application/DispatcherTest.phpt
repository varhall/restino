<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Contributte\Tester\Toolkit;
use Tester\Assert;
use Varhall\Restino\Results\IResult;
use Varhall\Restino\Results\Result;
use Varhall\Restino\Schema\Endpoint;
use Nette\Http\Request as HttpRequest;
use Nette\Http\Response as HttpResponse;
use Nette\DI\Container;
use Varhall\Restino\Controllers\ActionFactory;
use Varhall\Restino\Controllers\RestRequest;
use Varhall\Restino\Filters\Chain;
use Varhall\Restino\Application\Dispatcher;
use Nette\Application\Request;
use Nette\Application\Response;
use Varhall\Restino\Filters\Secured;
use Varhall\Restino\Filters\Role;

/// Test classes

#[Secured]
class FilterAttributesController implements \Varhall\Restino\Controllers\IController
{
    public function global(string $name): IResult
    {
        return new Result(['message' => "Hello, $name!"]);
    }

    #[Secured]
    public function both(): IResult
    {
        return new Result(['message' => 'This is a secured endpoint']);
    }

    #[Role('test')]
    public function local(): IResult
    {
        return new Result(['message' => 'This endpoint is not secured']);
    }
}


/// Test cases

Toolkit::test(function (): void {
    // fake controller
    $controller = new class implements \Varhall\Restino\Controllers\IController {
        public function hello(string $name): IResult {
            return new Result(['message' => "Hello, $name!"]);
        }
    };

    $endpoint = new Endpoint(
        path: '/hello/{name}',
        method: 'GET',
        controller: get_class($controller),
        action: 'hello'
    );

    $httpRequest = mock(HttpRequest::class);
    $httpResponse = new HttpResponse();

    // fake container
    $container = mock(Container::class);
    $container->shouldReceive('getByType')->andReturn($controller);

    // fake action factory
    $actionFactory = mock(ActionFactory::class);
    $actionFactory->shouldReceive('create')
        ->andReturnUsing(function (ReflectionMethod $method, RestRequest $request) {
            return new \Varhall\Restino\Controllers\Action($method, [ 'name' => 'foo' ]);
        });

    // fake middleware chain – žádné middleware, jen přepošle volání
    $filters = mock(Chain::class);
    $filters->shouldReceive('build')
        ->with(Mockery::type('callable'), 'hello')
        ->andReturnUsing(fn($callback) => $callback);

    // dispatcher
    $dispatcher = new Dispatcher(
        $httpRequest,
        $httpResponse,
        $container,
        $actionFactory,
        $filters
    );

    // simuluje Application Request
    $request = new Request(
        name: 'Restino:' . get_class($controller),
        method: 'GET',
        params: [
            '_endpoint' => $endpoint,
            'name' => 'Tester'
        ]
    );

    /** @var Response $response */
    $response = $dispatcher->run($request);

    Assert::type(\Nette\Application\Response::class, $response);
}, 'Dispatcher: dispatcher run');


Toolkit::test(function (): void {
    $httpRequest = mock(HttpRequest::class);
    $httpResponse = new HttpResponse();
    $container = mock(Container::class);
    $actionFactory = mock(ActionFactory::class);
    $filters = mock(Chain::class);

    $dispatcher = new Dispatcher(
        $httpRequest,
        $httpResponse,
        $container,
        $actionFactory,
        $filters
    );


    $endpoints = [
        new Endpoint(path: '/global', method: 'GET', controller: FilterAttributesController::class, action: 'global'),
        new Endpoint(path: '/both', method: 'GET', controller: FilterAttributesController::class, action: 'both'),
        new Endpoint(path: '/local', method: 'GET', controller: FilterAttributesController::class, action: 'local')
    ];

    $attributes = [
        'global' => [Secured::class],
        'both' => [Secured::class, Secured::class],
        'local' => [Secured::class, Role::class]
    ];

    foreach ($endpoints as $endpoint) {
        $attrs = $dispatcher->getFilterAttributes($endpoint);
        Assert::equal(
            $attributes[$endpoint->action],
            array_map(fn($attr) => $attr->getName(), $attrs)
        );
    }

}, 'Dispatcher: dispatcher instance');