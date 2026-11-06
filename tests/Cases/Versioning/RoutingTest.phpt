<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Tests\Fixtures\Versioning\BooksController;
use Tests\Fixtures\Versioning\Kit;
use Varhall\Restino\Controllers\Attributes\Get;
use Varhall\Restino\Controllers\Attributes\Path;
use Varhall\Restino\Controllers\Attributes\Post;
use Varhall\Restino\Controllers\IController;
use Varhall\Restino\Router\ApiRouter;
use Varhall\Restino\Router\ApiRouterFactory;
use Varhall\Restino\Schema\Endpoint;
use Varhall\Restino\Schema\SchemaGenerator;
use Varhall\Restino\Versioning\ClientVersion;
use Varhall\Restino\Versioning\HeaderVersionResolver;


/// fixtures

#[Path('/orders')]
class OverlappingController implements IController
{
    #[Get('/', before: 3)]
    public function old() {}

    #[Get('/', since: 2)]
    public function new() {}
}

#[Path('/orders')]
class UnboundedController implements IController
{
    #[Get('/', since: 2)]
    public function versioned() {}

    #[Get('/')]
    public function unversioned() {}
}

#[Path('/orders')]
class AdjacentController implements IController
{
    #[Get('/{id}', before: 2)]
    public function v1(int $id) {}

    #[Get('/{id}', since: 2, before: 4)]
    public function v2(int $id) {}

    #[Get('/{id}', since: 4)]
    public function v4(int $id) {}

    #[Post('/{id}')]
    public function update(int $id) {}
}


/// endpoint

Toolkit::test(function (): void {
    $endpoint = new Endpoint('/a', 'GET', 'C', 'a', since: 2, before: 4);

    Assert::false($endpoint->accepts(1));
    Assert::true($endpoint->accepts(2));
    Assert::true($endpoint->accepts(3));
    Assert::false($endpoint->accepts(4));
    Assert::same('since 2, before 4', $endpoint->describeRange());

    $all = new Endpoint('/a', 'GET', 'C', 'a');
    Assert::true($all->accepts(0));
    Assert::true($all->accepts(100));
    Assert::false($all->isVersioned());
    Assert::same('all versions', $all->describeRange());
}, 'Endpoint: accepts()');


Toolkit::test(function (): void {
    $e = fn(?int $since, ?int $before) => new Endpoint('/a', 'GET', 'C', 'a', $since, $before);

    Assert::false($e(null, 3)->overlaps($e(3, null)));
    Assert::true($e(null, 3)->overlaps($e(2, null)));
    Assert::true($e(null, null)->overlaps($e(5, 6)));
    Assert::false($e(1, 2)->overlaps($e(2, 4)));
    Assert::true($e(1, 3)->overlaps($e(2, 4)));

    Assert::exception(fn() => $e(3, 3), \Nette\InvalidArgumentException::class);
}, 'Endpoint: overlaps() and invalid range');


/// schema generator

Toolkit::test(function (): void {
    $schema = (new SchemaGenerator([ new AdjacentController() ]))->getSchema();
    $endpoints = $schema->groups[0]->endpoints;

    Assert::count(4, $endpoints);
    Assert::same([ null, 2 ], [ $endpoints[0]->since, $endpoints[0]->before ]);
    Assert::same([ 2, 4 ], [ $endpoints[1]->since, $endpoints[1]->before ]);
    Assert::same([ 4, null ], [ $endpoints[2]->since, $endpoints[2]->before ]);
}, 'SchemaGenerator: non-overlapping version ranges on the same path are allowed');


Toolkit::test(function (): void {
    Assert::exception(
        fn() => (new SchemaGenerator([ new OverlappingController() ]))->getSchema(),
        \Nette\InvalidStateException::class,
        '%a%OverlappingController::new, since 2%a%OverlappingController::old, before 3%a%'
    );

    Assert::exception(
        fn() => (new SchemaGenerator([ new UnboundedController() ]))->getSchema(),
        \Nette\InvalidStateException::class,
        '%a%all versions%a%'
    );
}, 'SchemaGenerator: overlapping version ranges are reported');


/// router

function routeAction(ApiRouter $router, string $path, array $headers = [], string $method = 'GET'): ?string
{
    $match = $router->match(Kit::httpRequest($headers, $method, "https://example.com{$path}"));
    return $match['action'] ?? null;
}

Toolkit::test(function (): void {
    $version = new ClientVersion(Kit::settings(5, 1), new HeaderVersionResolver());
    $router = new ApiRouter((new SchemaGenerator([ new AdjacentController() ]))->getSchema(), $version);

    Assert::same('v1', routeAction($router, '/orders/1', [ 'X-Api-Version' => '1' ]));
    Assert::same('v2', routeAction($router, '/orders/1', [ 'X-Api-Version' => '2' ]));
    Assert::same('v2', routeAction($router, '/orders/1', [ 'X-Api-Version' => '3' ]));
    Assert::same('v4', routeAction($router, '/orders/1', [ 'X-Api-Version' => '5' ]));

    // router resolves client version for the rest of the request
    Assert::same(5, $version->get());

    Assert::same('update', routeAction($router, '/orders/1', [ 'X-Api-Version' => '1' ], 'POST'));
    Assert::same(1, $version->get());
}, 'ApiRouter: endpoint is selected by client version');


Toolkit::test(function (): void {
    $router = new ApiRouter((new SchemaGenerator([ new AdjacentController() ]))->getSchema());

    Assert::same('v1', routeAction($router, '/orders/1', [ 'X-Api-Version' => '5' ]));
}, 'ApiRouter: without ClientVersion ranges are ignored (first match)');


Toolkit::test(function (): void {
    $version = new ClientVersion(Kit::settings(3, 1), new HeaderVersionResolver());
    $router = (new ApiRouterFactory(new SchemaGenerator([ new BooksController() ]), $version))->create();

    Assert::same('legacyCatalog', routeAction($router, '/books/catalog', [ 'X-Api-Version' => '1' ]));
    Assert::same('catalog', routeAction($router, '/books/catalog', [ 'X-Api-Version' => '2' ]));

    // unknown client (missing header, reject policy) gets the oldest endpoint and is rejected by the filter
    Assert::same('legacyCatalog', routeAction($router, '/books/catalog'));
    Assert::same(0, $version->get());

    $plain = (new ApiRouterFactory(new SchemaGenerator([ new BooksController() ])))->create();
    Assert::same('legacyCatalog', routeAction($plain, '/books/catalog', [ 'X-Api-Version' => '2' ]));
}, 'ApiRouterFactory: creates router with or without versioning');
