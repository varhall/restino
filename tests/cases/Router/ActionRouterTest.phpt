<?php

namespace Tests\Cases\Router;

use Nette\Http\UrlScript;
use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Varhall\Restino\Controllers\Attributes\Get;
use Varhall\Restino\Controllers\Attributes\Post;
use Varhall\Restino\Controllers\RestController;
use Varhall\Restino\Mapping\MappingService;
use Varhall\Restino\Router\ActionRouter;

require_once __DIR__ . '/../../bootstrap.php';

class ActionRouterTest extends BaseTestCase
{
    public function testRoute_index()
    {
        $this->test('GET', 'http://localhost/api/foo', 'index');
    }

    public function testRoute_get()
    {
        $this->test('GET', 'http://localhost/api/foo/1', 'get');
    }

    public function testRoute_create()
    {
        $this->test('POST', 'http://localhost/api/foo', 'create');
    }

    public function testRoute_update()
    {
        $this->test('PUT', 'http://localhost/api/foo/1', 'update');
    }

    public function testRoute_delete()
    {
        $this->test('DELETE', 'http://localhost/api/foo/1', 'delete');
    }

    public function testRoute_test_get()
    {
        $this->test('GET', 'http://localhost/api/foo/test/get', 'test_get');
    }

    public function testRoute_test_post()
    {
        $this->test('POST', 'http://localhost/api/foo/test/post', 'test_post');
    }

    public function testRoute_invalid()
    {
        $this->test('GET', 'http://localhost/api/foo/test/unknown', 'invalid');
    }



    private function test(string $method, string $url, string $function): void
    {
        $router = new ActionRouter(
            new \Nette\Http\Request(new UrlScript($url, '/'), method: $method),
            mock(MappingService::class)
        );

        $request = new \Nette\Application\Request('Api', $method, [ 'mask' => 'api/<presenter>' ]);
        $action = $router->route($this->prepareController(), $request);

        Assert::equal($function, $action->getName());
    }

    private function prepareController(): RestController
    {
        return new class extends RestController {
            public function index(): void {}
            public function get(): void {}
            public function create(): void {}
            public function update(): void {}
            public function delete(): void {}

            #[Get('/test/get')]
            public function test_get(): void {}

            #[Post('/test/post')]
            public function test_post(): void {}
        };
    }
}

(new ActionRouterTest())->run();
