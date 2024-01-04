<?php

namespace Tests\Cases\Controllers;

use Nette\Application\Request;
use Nette\Application\Responses\JsonResponse;
use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Varhall\Restino\Controllers\RestController;
use Varhall\Restino\Middlewares\Chain;
use Varhall\Restino\Middlewares\Factory;


require_once __DIR__ . '/../../bootstrap.php';

class RestControllerTest extends BaseTestCase
{
    protected function setupRestController(RestController $controller): void
    {
        $context = null;
        $router = null;
        $httpRequest = spy(\Nette\Http\IRequest::class);
        $httpResponse = spy(\Nette\Http\IResponse::class);
        $user = spy(\Nette\Security\User::class);
        $factory = spy(Factory::class);
        $middleware = spy(Chain::class);
        $actionRouter = spy(\Varhall\Restino\Router\ActionRouter::class);

        $controller->injectPrimary($context, $router, $httpRequest, $httpResponse, $factory, $middleware, $actionRouter, $user);
    }

    public function testRun(): void
    {
        $restController = new class() extends RestController {
            public function index(string $name, string $surname) {
                Assert::equal('pepa', $name);
                Assert::equal('novak', $surname);

                return [ 'key' => 'value' ];
            }
        };
        $this->setupRestController($restController);

        $response = $restController->run(new Request('', null, []));

        Assert::type(JsonResponse::class, $response);
    }
}

(new RestControllerTest())->run();
