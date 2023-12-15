<?php

namespace tests\cases\Middlewares\Attributes;

use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Varhall\Restino\Middlewares\Attributes\Role;
use Varhall\Restino\Middlewares\Factory;
use Varhall\Restino\Middlewares\Operations\RoleMiddleware;

require_once __DIR__ . '/../../../bootstrap.php';

class RoleTest extends BaseTestCase
{
    public function testMiddleware()
    {
        $factory = mock(Factory::class);
        $factory->shouldReceive('role')->with('admin')->andReturn(mock(RoleMiddleware::class));

        $attribute = new Role('admin');

        Assert::type(RoleMiddleware::class, $attribute->middleware($factory));
    }
}

(new RoleTest())->run();
