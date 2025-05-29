<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Contributte\Tester\Toolkit;
use Tester\Assert;
use Varhall\Restino\Schema\Endpoint;
use Varhall\Restino\Schema\Group;
use Varhall\Restino\Schema\Schema;
use Varhall\Restino\Router\ApiRouter;
use Nette\Http\UrlScript;
use Nette\Http\IRequest;
use Nette\Application\UI\Presenter;



Toolkit::test(function (): void {
    // endpoint: GET /users/{id}
    $endpoint = new Endpoint('/users/{id}', 'GET', 'App/Controllers/MyController', 'show');
    $group = new Group('/api');
    $group->add($endpoint);
    $schema = new Schema([$group]);

    $router = new ApiRouter($schema);

    // připravíme požadavek
    $url = new UrlScript('https://example.com/api/users/123', '/');
    $httpRequest = mock(IRequest::class);
    $httpRequest->shouldReceive('getUrl')->andReturn($url);
    $httpRequest->shouldReceive('getMethod')->andReturn('GET');
    $httpRequest->shouldReceive('getQuery')->andReturn(['foo' => 'bar']);
    $httpRequest->shouldReceive('getRawBody')->andReturn('{"extra":"value"}');

    $params = $router->match($httpRequest);

    Assert::notNull($params);
    Assert::equal('Restino:App/Controllers/MyController', $params[Presenter::PresenterKey]);
    Assert::equal('show', $params['action']);
    Assert::equal('123', $params['id']);
    Assert::equal('bar', $params['foo']);
    Assert::equal('value', $params['extra']);
    Assert::type(Endpoint::class, $params['_endpoint']);

}, 'ApiRouter: správně namapuje endpoint podle URL a metody');


Toolkit::test(function (): void {
    $endpoint = new Endpoint('/users/{id}', 'POST', 'App/Controllers/MyController', 'create');
    $group = new Group('/api');
    $group->add($endpoint);
    $schema = new Schema([$group]);

    $router = new ApiRouter($schema);

    $url = new UrlScript('https://example.com/api/users/123', '/');
    $httpRequest = mock(IRequest::class);
    $httpRequest->shouldReceive('getUrl')->andReturn($url);
    $httpRequest->shouldReceive('getMethod')->andReturn('GET'); // metoda nesouhlasí
    $httpRequest->shouldReceive('getQuery')->andReturn([]);
    $httpRequest->shouldReceive('getRawBody')->andReturn('');

    $params = $router->match($httpRequest);

    Assert::null($params);

}, 'ApiRouter: vrátí null, pokud metoda nesouhlasí');


Toolkit::test(function (): void {
    $schema = new Schema([]);
    $router = new ApiRouter($schema);

    Assert::throws(function () use ($router) {
        $router->constructUrl([], new UrlScript('https://example.com', '/'));
    }, \Nette\NotSupportedException::class);

}, 'ApiRouter: constructUrl vyhodí výjimku');