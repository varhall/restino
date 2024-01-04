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
    public function testMatch()
    {
        $mask = 'api/<presenter>';
        $route = new RestRoute($mask);

        $url = new UrlScript('http://localhost/api/foo?value=test', '/');
        $httpRequest = new Request($url, method: 'GET');

        $result = $route->match($httpRequest);

        Assert::equal([
            'presenter' => 'Foo',
            'mask'      => $mask,
        ], $result);
    }
}

(new RestRouteTest())->run();
