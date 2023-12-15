<?php

namespace Tests\Cases\Middlewares;

use Nette\DI\Container;
use Nette\Http\IResponse;
use Nette\InvalidArgumentException;
use Nette\Security\User;
use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Varhall\Restino\Middlewares\Factory;
use Varhall\Restino\Middlewares\Operations\AuthenticationMiddleware;
use Varhall\Restino\Middlewares\Operations\CollectionMiddleware;
use Varhall\Restino\Middlewares\Operations\CorsMiddleware;
use Varhall\Restino\Middlewares\Operations\ExpandMiddleware;
use Varhall\Restino\Middlewares\Operations\RoleMiddleware;

require_once __DIR__ . '/../../bootstrap.php';

class FactoryTest extends BaseTestCase
{
    public function testCreate()
    {
        $factory = new Factory(mock(Container::class));
        Assert::type(CollectionMiddleware::class, $factory->create(CollectionMiddleware::class));
    }

    public function testCreate_invalid()
    {
        $factory = new Factory(mock(Container::class));
        Assert::exception(fn() => $factory->create(static::class), InvalidArgumentException::class);
    }

    public function testAuthentication()
    {
        $container = mock(Container::class);
        $container->shouldReceive('getByType')->with(User::class, false)->andReturn(mock(User::class));

        $factory = new Factory($container);
        Assert::type(AuthenticationMiddleware::class, $factory->authentication());
    }

    public function testRole()
    {
        $container = mock(Container::class);
        $container->shouldReceive('getByType')->with(User::class)->andReturn(mock(User::class));

        $factory = new Factory($container);
        Assert::type(RoleMiddleware::class, $factory->role('admin'));
    }

    public function testCors()
    {
        $container = mock(Container::class);
        $container->shouldReceive('getByType')->with(IResponse::class)->andReturn(mock(IResponse::class));

        $factory = new Factory($container);
        Assert::type(CorsMiddleware::class, $factory->cors());
    }

    public function testExpand()
    {
        $factory = new Factory(mock(Container::class));
        Assert::type(ExpandMiddleware::class, $factory->expand([]));
    }
}

(new FactoryTest())->run();
