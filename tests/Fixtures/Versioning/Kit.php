<?php

namespace Tests\Fixtures\Versioning;

use Nette\Application\Request;
use Nette\DI\Container;
use Nette\Http\IResponse;
use Nette\Http\Request as HttpRequest;
use Nette\Http\UrlScript;
use Varhall\Restino\Controllers\RestRequest;
use Varhall\Restino\Filters\Context;
use Varhall\Restino\Schema\Endpoint;
use Varhall\Restino\Versioning\ClientVersion;
use Varhall\Restino\Versioning\HeaderVersionResolver;
use Varhall\Restino\Versioning\Settings;

/**
 * Helpers for versioning tests
 */
class Kit
{
    public static function settings(int $current = 3, int $minimum = 1, string $missing = Settings::MISSING_REJECT, bool $vary = true): Settings
    {
        return new Settings($current, $minimum, $missing, 426, 'X-Api-Min-Version', 'X-Api-Current-Version', $vary);
    }

    public static function httpRequest(array $headers = [], string $method = 'GET', string $url = 'https://example.com/'): HttpRequest
    {
        return new HttpRequest(new UrlScript($url, '/'), headers: $headers, method: $method);
    }

    /**
     * Client version resolved from X-Api-Version header (null = header not sent)
     */
    public static function version(?string $header, ?Settings $settings = null): ClientVersion
    {
        $version = new ClientVersion($settings ?? self::settings(), new HeaderVersionResolver());
        $version->resolve(self::httpRequest($header === null ? [] : [ 'X-Api-Version' => $header ]));

        return $version;
    }

    /**
     * Mock of HTTP response which records headers
     *
     * @return array{0: IResponse, 1: \ArrayObject}
     */
    public static function response(array $headers = []): array
    {
        $recorded = new \ArrayObject($headers);

        $response = \Mockery::mock(IResponse::class);
        $response->shouldReceive('setHeader')->andReturnUsing(function ($name, $value) use ($recorded, $response) {
            $recorded[$name] = $value;
            return $response;
        });
        $response->shouldReceive('getHeader')->andReturnUsing(fn($name) => $recorded[$name] ?? null);

        return [ $response, $recorded ];
    }

    public static function context(Endpoint $endpoint, IResponse $response, array $parameters = [], string $method = 'GET', array $services = []): Context
    {
        $container = \Mockery::mock(Container::class);
        $container->shouldReceive('getByType')->with(IResponse::class)->andReturn($response);
        $container->shouldReceive('findByType')->andReturnUsing(fn($type) => array_keys(array_filter($services, fn($s) => $s instanceof $type)));
        $container->shouldReceive('getService')->andReturnUsing(fn($name) => $services[$name]);

        $request = new Request('Restino:' . $endpoint->controller, $method, $parameters + [ '_endpoint' => $endpoint, 'action' => $endpoint->action ]);

        return new Context($container, new RestRequest($request), $endpoint);
    }

    public static function endpoint(string $action, string $method = 'GET', string $path = '/books'): Endpoint
    {
        return new Endpoint($path, $method, BooksController::class, $action);
    }
}
