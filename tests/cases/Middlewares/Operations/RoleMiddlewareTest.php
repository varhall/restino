<?php

namespace tests\cases\Middlewares\Operations;

use Nette\Security\User;
use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Varhall\Restino\Controllers\RestRequest;
use Varhall\Restino\Middlewares\Operations\AuthenticationMiddleware;
use Varhall\Restino\Middlewares\Operations\RoleMiddleware;
use Varhall\Restino\Results\Result;
use Varhall\Restino\Results\Termination;

require_once __DIR__ . '/../../../bootstrap.php';

class RoleMiddlewareTest extends BaseTestCase
{
    public function testExecute_Yes()
    {
        $user = mock(User::class);
        $user->shouldReceive('isInRole')->with('admin')->andReturn(true);

        $middleware = new RoleMiddleware($user, 'admin');

        $result = $middleware(mock(RestRequest::class), fn($x) => new Result(1));

        Assert::type(Result::class, $result);
    }

    public function testExecute_No()
    {
        $user = mock(User::class);
        $user->shouldReceive('isInRole')->with('admin')->andReturn(false);

        $middleware = new RoleMiddleware($user, 'admin');

        $result = $middleware(mock(RestRequest::class), fn($x) => new Result(1));

        Assert::type(Termination::class, $result);
    }
}

(new RoleMiddlewareTest())->run();
