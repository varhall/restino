<?php

namespace Tests\Cases\Controllers;

use Nette\Application\Request;
use Nette\Application\Responses\JsonResponse;
use Nette\Schema\Message;
use Nette\Schema\ValidationException;
use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Varhall\Restino\Controllers\RestController;
use Varhall\Restino\Mapping\MappingService;
use Varhall\Restino\Middlewares\Chain;
use Varhall\Restino\Middlewares\Factory;


require_once __DIR__ . '/../../bootstrap.php';

class RestControllerTest extends BaseTestCase
{
    protected function setupRestController(RestController $controller, MappingService $mapping): void
    {
        $context = null;
        $router = null;
        $httpRequest = mock(\Nette\Http\IRequest::class);
        $httpResponse = mock(\Nette\Http\IResponse::class);
        $httpResponse->shouldReceive('setCode');
        $user = mock(\Nette\Security\User::class);
        $factory = mock(Factory::class);
        $middleware = mock(Chain::class);
        $middleware->shouldReceive('chain')->andReturnUsing(fn($r) => $r);

        $controller->injectPrimary($context, $router, $httpRequest, $httpResponse, $mapping, $factory, $middleware, $user);
    }

    public function testRun(): void
    {
        $data = [ 'name' => 'pepa', 'surname' => 'novak' ];
        $request = new Request('', null, [ 'action' => 'index', 'data' => $data ]);

        $mapping = mock(MappingService::class);
        foreach (array_values($data) as $m) {
            $mapping->shouldReceive('process')->once()->andReturn($m);
        }

        $restController = new class() extends RestController {
            public function index(string $name, string $surname) {
                Assert::equal('pepa', $name);
                Assert::equal('novak', $surname);

                return [ 'key' => 'value' ];
            }
        };
        $this->setupRestController($restController, $mapping);

        // Act
        $response = $restController->run($request);

        // Assert
        Assert::type(JsonResponse::class, $response);

        // Additional assertions based on your specific logic
    }

    public function testError(): void
    {
        $data = [ 'name' => 'pepa', 'surname' => 'novak' ];
        $request = new Request('', null, [ 'action' => 'index', 'data' => $data ]);

        $mapping = mock(MappingService::class);
        foreach (array_keys($data) as $m) {
            $mapping->shouldReceive('process')->once()->andThrow(new ValidationException('', [ new Message('invalid value', '', [$m]) ]));
        }

        $restController = new class() extends RestController {
            public function index(string $name, string $surname) {
                Assert::fail('Must not be called');
            }
        };
        $this->setupRestController($restController, $mapping);

        // Act
        $response = $restController->run($request);

        // Assert
        Assert::equal([ 'name' => 'invalid value', 'surname' => 'invalid value' ], $response->getPayload());
    }
}

(new RestControllerTest())->run();
