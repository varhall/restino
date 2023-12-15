<?php

namespace tests\cases\Middlewares\Operations;

use Nette\Security\User;
use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Varhall\Restino\Controllers\RestRequest;
use Varhall\Restino\Middlewares\Operations\AuthenticationMiddleware;
use Varhall\Restino\Results\Result;
use Varhall\Restino\Results\Termination;

require_once __DIR__ . '/../../../bootstrap.php';

class AuthenticationMiddlewareTest extends BaseTestCase
{
    public function testExecute_Authenticated()
    {
        $user = mock(User::class);
        $user->shouldReceive('isLoggedIn')->andReturn(true);

        $middleware = new AuthenticationMiddleware($user);

        $result = $middleware(mock(RestRequest::class), fn($x) => new Result(1));

        Assert::type(Result::class, $result);
    }

    public function testExecute_Anonymous()
    {
        $user = mock(User::class);
        $user->shouldReceive('isLoggedIn')->andReturn(false);

        $middleware = new AuthenticationMiddleware($user);

        $result = $middleware(mock(RestRequest::class), fn($x) => new Result(1));

        Assert::type(Termination::class, $result);
    }
}

(new AuthenticationMiddlewareTest())->run();
