<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Nette\Application\Request;
use Nette\Bridges\ApplicationDI\ApplicationExtension;
use Nette\Bridges\ApplicationDI\RoutingExtension;
use Nette\Bridges\HttpDI\HttpExtension;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Http\Request as HttpRequest;
use Nette\Http\UrlScript;
use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Tests\Fixtures\Versioning\BooksController;
use Varhall\Restino\DI\RestinoExtension;
use Varhall\Restino\Filters\Chain;
use Varhall\Restino\Filters\Cors;
use Varhall\Restino\Results\Serializer;
use Varhall\Restino\Router\ApiRouterFactory;
use Varhall\Restino\Versioning\ClientVersion;
use Varhall\Restino\Versioning\Compatibility;
use Varhall\Restino\Versioning\Inspection\Inventory;
use Varhall\Restino\Versioning\Settings;
use Varhall\Restino\Versioning\VersionResolver;


/// fixtures

class QueryVersionResolver implements VersionResolver
{
    public function resolve(IRequest $request): ?int
    {
        $value = $request->getQuery('v');
        return $value === null ? null : (int) $value;
    }

    public function varyHeaders(): array
    {
        return [];
    }
}

const VERSIONING = [
    'current'   => 3,
    'minimum'   => 2,
    'header'    => 'X-Client-Version',
    'advertise' => [ 'minimum' => 'X-Client-Min-Version', 'current' => 'X-Client-Current-Version' ],
];

function createContainer(array $restino, array $services = []): Container
{
    $loader = new ContainerLoader(sys_get_temp_dir() . '/restino-tests/' . getmypid(), true);

    $class = $loader->load(function (Compiler $compiler) use ($restino, $services) {
        $compiler->addExtension('http', new HttpExtension(true));
        $compiler->addExtension('routing', new RoutingExtension());
        $compiler->addExtension('application', new ApplicationExtension(false));
        $compiler->addExtension('restino', new RestinoExtension());

        $compiler->addConfig([
            'restino'   => $restino,
            'services'  => array_merge([ BooksController::class ], $services),
        ]);
    }, md5(serialize([ $restino, $services ])));

    return new $class();
}

/**
 * Runs the request through ApiRouter and Dispatcher
 *
 * @return array{0: mixed, 1: int} payload and HTTP code
 */
function dispatch(array $restino, string $method, string $path, array $headers = [], ?array $body = null): array
{
    $container = createContainer($restino);

    // Nette\Http\Response takes initial code from the global state of the previous request in the process
    $container->getByType(IResponse::class)->setCode(200);

    $httpRequest = new HttpRequest(
        new UrlScript("https://example.com{$path}", '/'),
        headers: $headers,
        method: $method,
        rawBodyCallback: fn() => $body === null ? null : json_encode($body),
    );

    $container->removeService('http.request');
    $container->addService('http.request', $httpRequest);

    $router = $container->getByType(ApiRouterFactory::class)->create();
    $parameters = $router->match($httpRequest);
    Assert::notNull($parameters, "Route {$method} {$path} not found");

    $response = $container->getService('restino.dispatcher')->run(new Request($parameters['presenter'], $method, $parameters));

    return [ $response->getPayload(), $container->getByType(IResponse::class)->getCode() ];
}


/// configuration

Toolkit::test(function (): void {
    $container = createContainer([ 'filters' => [ 'cors' => Cors::class ] ]);

    Assert::null($container->getByType(ClientVersion::class, false));
    Assert::null($container->getByType(Compatibility::class, false));
    Assert::null($container->getByType(Serializer::class)->getCompatibility());
    Assert::same([ 'cors' ], array_keys($container->getByType(Chain::class)->getFilters()));
}, 'Extension: without versioning section nothing is registered');


Toolkit::test(function (): void {
    $container = createContainer([ 'filters' => [ 'cors' => Cors::class ], 'versioning' => VERSIONING ]);

    $settings = $container->getByType(Settings::class);
    Assert::same(3, $settings->current);
    Assert::same(2, $settings->minimum);
    Assert::same(Settings::MISSING_REJECT, $settings->missing);
    Assert::same(426, $settings->rejectionCode);
    Assert::same([ 'X-Client-Min-Version', 'X-Client-Current-Version' ], $settings->advertisedHeaders());

    Assert::type(Compatibility::class, $container->getByType(Serializer::class)->getCompatibility());
    Assert::type(Inventory::class, $container->getByType(Inventory::class));

    // versioning filter is registered first, but runs inside Cors
    $chain = $container->getByType(Chain::class);
    Assert::same([ 'versioning', 'cors' ], array_keys($chain->getFilters()));
    Assert::same([ 'cors', 'versioning' ], array_keys($chain->getOrderedFilters()));
}, 'Extension: versioning services and filter position');


Toolkit::test(function (): void {
    $container = createContainer([ 'versioning' => [ 'current' => 2, 'minimum' => 1, 'advertise' => false, 'missing' => 'current' ] ]);

    $settings = $container->getByType(Settings::class);
    Assert::same([], $settings->advertisedHeaders());
    Assert::same(Settings::MISSING_CURRENT, $settings->missing);
}, 'Extension: advertising can be disabled');


Toolkit::test(function (): void {
    Assert::exception(
        fn() => createContainer([ 'versioning' => [ 'current' => 2, 'minimum' => 3 ] ]),
        \Nette\DI\InvalidConfigurationException::class,
        '%a%minimum%a%'
    );

    Assert::exception(
        fn() => createContainer([ 'versioning' => [ 'current' => 2, 'minimum' => 1, 'missing' => 'never' ] ]),
        \Nette\DI\InvalidConfigurationException::class
    );
}, 'Extension: invalid configuration');


Toolkit::test(function (): void {
    $container = createContainer([ 'versioning' => VERSIONING ], [ QueryVersionResolver::class ]);

    Assert::type(QueryVersionResolver::class, $container->getByType(VersionResolver::class));
}, 'Extension: custom VersionResolver replaces the default one');


/// full request

Toolkit::test(function (): void {
    $config = [ 'versioning' => VERSIONING ];

    [ $payload, $code ] = dispatch($config, 'GET', '/books/7', [ 'X-Client-Version' => '3' ]);
    Assert::same(200, $code);
    Assert::same([ 'title' => 'Book 7', 'author' => [ 'firstName' => 'Karel', 'lastName' => 'Capek' ] ], $payload);

    [ $payload ] = dispatch($config, 'GET', '/books/7', [ 'X-Client-Version' => '2' ]);
    Assert::same([ 'title' => 'Book 7', 'author' => [ 'name' => 'Karel Capek' ], 'initials' => 'KC' ], $payload);
}, 'Request: response is downgraded to client version');


Toolkit::test(function (): void {
    $config = [ 'versioning' => VERSIONING ];

    [ $payload, $code ] = dispatch($config, 'GET', '/books/7');
    Assert::same(426, $code);
    Assert::same([ 'error' => 'client_outdated', 'min_version' => 2, 'current_version' => 3 ], $payload);

    [ , $code ] = dispatch($config, 'GET', '/books/7', [ 'X-Client-Version' => '1' ]);
    Assert::same(426, $code);

    [ $payload, $code ] = dispatch($config, 'GET', '/books/cover');
    Assert::same(200, $code);
    Assert::same([ 'cover' => 'image' ], $payload);
}, 'Request: outdated and unknown clients are rejected, unversioned endpoint is accessible');


Toolkit::test(function (): void {
    $config = [ 'versioning' => VERSIONING ];

    [ $payload ] = dispatch($config, 'POST', '/books/authors', [ 'X-Client-Version' => '2' ], [ 'name' => 'Jan Neruda' ]);

    // input upgraded to shape 3, output downgraded back to shape 2
    Assert::same([ 'name' => 'Jan Neruda' ], $payload);
}, 'Request: input is upgraded and output downgraded');


Toolkit::test(function (): void {
    $config = [ 'versioning' => VERSIONING ];

    Assert::same([ 'count' => 10 ], dispatch($config, 'GET', '/books/stats', [ 'X-Client-Version' => '2' ])[0]);
    Assert::same([ 'total' => 10, 'active' => 7 ], dispatch($config, 'GET', '/books/stats', [ 'X-Client-Version' => '3' ])[0]);
}, 'Request: action output is downgraded');


Toolkit::test(function (): void {
    $config = [ 'versioning' => [ 'current' => 3, 'minimum' => 1, 'header' => 'X-Client-Version' ] ];

    Assert::same([ 'catalog' => 'legacy' ], dispatch($config, 'GET', '/books/catalog', [ 'X-Client-Version' => '1' ])[0]);
    Assert::same([ 'catalog' => 'current' ], dispatch($config, 'GET', '/books/catalog', [ 'X-Client-Version' => '2' ])[0]);

    [ $payload, $code ] = dispatch($config, 'GET', '/books/recommended', [ 'X-Client-Version' => '2' ]);
    Assert::same(426, $code);
    Assert::same(3, $payload['min_version']);
}, 'Request: routing by version and MinVersion');


Toolkit::test(function (): void {
    [ $payload, $code ] = dispatch([], 'GET', '/books/7');

    Assert::same(200, $code);
    Assert::same([ 'title' => 'Book 7', 'author' => [ 'firstName' => 'Karel', 'lastName' => 'Capek' ] ], $payload);
    Assert::same([ 'total' => 10, 'active' => 7 ], dispatch([], 'GET', '/books/stats')[0]);
}, 'Request: without versioning nothing is checked or converted');
