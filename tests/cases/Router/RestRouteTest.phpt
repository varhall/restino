<?php

namespace Tests\Cases\Router;

use Nette\Http\Request;
use Nette\Http\UrlScript;
use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Varhall\Restino\Router\RestRoute;

require_once __DIR__ . '/../../bootstrap.php';

class RestRouteTest extends BaseTestCase
{
    public function testIndex()
    {
        $route = new RestRoute('api/<presenter>[/<id>]');

        $url = new UrlScript('http://localhost/api/foo?value=test', '/');
        $httpRequest = new Request($url, method: 'GET');

        $result = $route->match($httpRequest);

        Assert::equal([
            'presenter' => 'Foo',
            'action' => 'index',
            'data' => ['value' => 'test'],
        ], $result);
    }

    public function testGet()
    {
        $route = new RestRoute('api/<presenter>[/<id>]');

        $url = new UrlScript('http://localhost/api/foo/123?value=test', '/');
        $httpRequest = new Request($url, method: 'GET');

        $result = $route->match($httpRequest);

        Assert::equal([
            'presenter' => 'Foo',
            'action' => 'get',
            'data' => ['id' => '123', 'value' => 'test'],
        ], $result);
    }

    public function testCreate()
    {
        $data = [ 'name' => 'pepa', 'surname' => 'novak' ];
        $route = new RestRoute('api/<presenter>[/<id>]');

        $url = new UrlScript('http://localhost/api/foo', '/');
        $httpRequest = new Request($url, method: 'POST', rawBodyCallback: fn() => json_encode($data));

        $result = $route->match($httpRequest);

        Assert::equal([
            'presenter' => 'Foo',
            'action' => 'create',
            'data' => $data,
        ], $result);
    }

    public function testUpdate()
    {
        $data = [ 'name' => 'pepa', 'surname' => 'novak' ];
        $route = new RestRoute('api/<presenter>[/<id>]');

        $url = new UrlScript('http://localhost/api/foo/123', '/');
        $httpRequest = new Request($url, method: 'PUT', rawBodyCallback: fn() => json_encode($data));

        $result = $route->match($httpRequest);

        Assert::equal([
            'presenter' => 'Foo',
            'action' => 'update',
            'data' => $data + [ 'id' => '123' ],
        ], $result);
    }

    public function testDelete()
    {
        $route = new RestRoute('api/<presenter>[/<id>]');

        $url = new UrlScript('http://localhost/api/foo/123', '/');
        $httpRequest = new Request($url, method: 'DELETE');

        $result = $route->match($httpRequest);

        Assert::equal([
            'presenter' => 'Foo',
            'action' => 'delete',
            'data' => [ 'id' => '123' ],
        ], $result);
    }
}

(new RestRouteTest())->run();
