<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Varhall\Restino\Results\IResult;
use Varhall\Restino\Results\SimpleResult;
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
        return new SimpleResult(['message' => "Hello, $name!"]);
    }

    #[Secured]
    public function both(): IResult
    {
        return new SimpleResult(['message' => 'This is a secured endpoint']);
    }

    #[Role('test')]
    public function local(): IResult
    {
        return new SimpleResult(['message' => 'This endpoint is not secured']);
    }
}


/// Test cases

function dispatcherTest(string $actionName): \Nette\Application\Responses\JsonResponse
{
    // fake controller
    $controller = new class implements \Varhall\Restino\Controllers\IController {
        public function setup(): void
        {

        }

        public function hello(string $name): IResult {
            return new SimpleResult(['message' => "Hello, $name!"]);
        }

        #[\Varhall\Restino\Filters\Map('xxx')]
        public function simple(string $name): string {
            return 'hello world';
        }

        public function collection(string $name): \Varhall\Utilino\Collections\ICollection {
            return new \Varhall\Utilino\Collections\ArrayCollection(['item1', 'item2']);
        }
    };

    $endpoint = new Endpoint(
        path: '/hello/{name}',
        method: 'GET',
        controller: get_class($controller),
        action: $actionName
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

    // fake middleware chain
    $filters = mock(Chain::class);

    if ($actionName === 'simple') {
        $filters->shouldReceive('add')
            ->with('attribute__' . \Varhall\Restino\Filters\Map::class . '_4', Mockery::type(\Varhall\Restino\Filters\Map::class))
            ->andReturnUsing(function ($name, $instance) {
                return mock(\Varhall\Restino\Filters\Configuration::class);
            });
    }

    $filters->shouldReceive('build')
        ->with(Mockery::type('callable'), $actionName)
        ->andReturnUsing(fn($callback) => $callback);

    // dispatcher
    $dispatcher = new Dispatcher(
        $httpRequest,
        $httpResponse,
        $container,
        $actionFactory,
        $filters
    );

    $request = new Request(
        name: 'Restino:' . get_class($controller),
        method: 'GET',
        params: [
            '_endpoint' => $endpoint,
            'name' => 'Tester'
        ]
    );

    return $dispatcher->run($request);
}


Toolkit::test(function (): void {
    $response = dispatcherTest('hello');

    Assert::type(\Nette\Application\Response::class, $response);
}, 'Dispatcher: dispatcher run');

Toolkit::test(function (): void {
    $response = dispatcherTest('simple');

    Assert::equal('hello world', $response->getPayload());
}, 'Dispatcher: dispatcher run');

Toolkit::test(function (): void {
    $response = dispatcherTest('collection');

    Assert::equal([ 'item1', 'item2' ], $response->getPayload());
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

    $request = new Request(
        name: 'Restino:' . 'xxx',
        method: 'GET',
        params: [
            'name' => 'Tester'
        ]
    );

    Assert::throws(function () use ($dispatcher, $request) {
        $dispatcher->getEndpoint($request);
    }, \Nette\InvalidStateException::class);
});