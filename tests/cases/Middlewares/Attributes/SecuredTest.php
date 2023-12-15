<?php

namespace tests\cases\Middlewares\Attributes;

use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Varhall\Restino\Middlewares\Attributes\Secured;
use Varhall\Restino\Middlewares\Factory;
use Varhall\Restino\Middlewares\Operations\AuthenticationMiddleware;

require_once __DIR__ . '/../../../bootstrap.php';

class SecuredTest extends BaseTestCase
{
    public function testMiddleware()
    {
        $factory = mock(Factory::class);
        $factory->shouldReceive('authentication')->andReturn(mock(AuthenticationMiddleware::class));

        $attribute = new Secured();

        Assert::type(AuthenticationMiddleware::class, $attribute->middleware($factory));
    }
}

(new SecuredTest())->run();
