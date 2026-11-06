<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Nette\Application\Request;
use Nette\DI\Container;
use Nette\Http\Request as HttpRequest;
use Nette\Http\Response as HttpResponse;
use Nette\Http\UrlScript;
use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Varhall\Restino\Application\Dispatcher;
use Varhall\Restino\Controllers\ActionFactory;
use Varhall\Restino\Controllers\IController;
use Varhall\Restino\Filters\Chain;
use Varhall\Restino\Filters\Context;
use Varhall\Restino\Filters\IFilter;
use Varhall\Restino\Mapping\MappingService;
use Varhall\Restino\Results\IResult;
use Varhall\Restino\Schema\Endpoint;


#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Counted implements IFilter
{
    public static int $runs = 0;

    public function execute(Context $context, callable $next): IResult
    {
        self::$runs++;
        return $next($context);
    }
}

class CountedController implements IController
{
    #[Counted]
    public function hello(): array
    {
        return [ 'hello' => 'world' ];
    }
}


Toolkit::test(function (): void {
    $controller = new CountedController();

    $container = mock(Container::class);
    $container->shouldReceive('getByType')->with(CountedController::class)->andReturn($controller);

    $chain = new Chain();
    $chain->add('global', fn(Context $context, callable $next) => $next($context));

    $dispatcher = new Dispatcher(
        new HttpRequest(new UrlScript('https://example.com/hello', '/')),
        new HttpResponse(),
        $container,
        new ActionFactory(new MappingService()),
        $chain
    );

    $endpoint = new Endpoint('/hello', 'GET', CountedController::class, 'hello');
    $request = new Request('Restino:' . CountedController::class, 'GET', [ '_endpoint' => $endpoint ]);

    Counted::$runs = 0;

    Assert::same([ 'hello' => 'world' ], $dispatcher->run($request)->getPayload());
    Assert::same([ 'hello' => 'world' ], $dispatcher->run($request)->getPayload());

    // attribute filter ran for each request and did not stay in the shared chain
    Assert::same(2, Counted::$runs);
    Assert::same([ 'global' ], array_keys($chain->getFilters()));
}, 'Dispatcher: attribute filters do not leak into the shared chain');
