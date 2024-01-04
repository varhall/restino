<?php

namespace Tests\Cases\Router;

use Nette\Http\Request;
use Nette\Http\UrlScript;
use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Varhall\Restino\Router\ActionRoute;

require_once __DIR__ . '/../../bootstrap.php';

class ActionRouteTest extends BaseTestCase
{
    public function testMatch_invalidMethod()
    {
        $route = new ActionRoute('POST', '/test', new \ReflectionMethod($this, 'foo'));

        $url = new UrlScript('http://localhost/api/foo?value=test', '/');
        $httpRequest = new Request($url, method: 'GET');

        $result = $route->match($httpRequest);

        Assert::null($result);
    }

    public function testMatch_invalidUrl()
    {
        $route = new ActionRoute('POST', '/test', new \ReflectionMethod($this, 'foo'));

        $url = new UrlScript('http://localhost/api/foo?value=test', '/');
        $httpRequest = new Request($url, method: 'POST');

        $result = $route->match($httpRequest);

        Assert::null($result);
    }

    public function testMatch_valid()
    {
        $route = new ActionRoute('POST', '/test/<id>', new \ReflectionMethod($this, 'foo'));

        $url = new UrlScript('http://localhost/api/foo/test/1?value=test', '/');
        $httpRequest = new Request($url, method: 'POST');

        $result = $route->match($httpRequest);

        Assert::equal([
            'id'    => '1',
            'value' => 'test'
        ], $result);
    }

    public function testParams_query()
    {
        $route = new ActionRoute('GET', '/test/<id>', new \ReflectionMethod($this, 'foo'));

        $url = new UrlScript('http://localhost/api/foo/test/1?value=test&foo=bar', '/');
        $httpRequest = new Request($url, method: 'GET');

        $result = $route->match($httpRequest);

        Assert::equal([
            'id'    => '1',
            'value' => 'test',
            'foo'   => 'bar'
        ], $result);
    }

    public function testParams_body()
    {
        $data = [ 'name' => 'pepa', 'surname' => 'novak' ];
        $route = new ActionRoute('POST', '/test/<id>', new \ReflectionMethod($this, 'foo'));

        $url = new UrlScript('http://localhost/api/foo/test/1?value=test&foo=bar', '/');
        $httpRequest = new Request($url, method: 'POST', rawBodyCallback: fn() => json_encode($data));

        $result = $route->match($httpRequest);

        Assert::equal(array_merge([
            'id'    => '1',
            'value' => 'test',
            'foo'   => 'bar'
        ], $data), $result);
    }

    private function foo() {}
}

(new ActionRouteTest())->run();
