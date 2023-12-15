<?php

namespace Varhall\Restino\Middlewares;

use Nette\DI\Container;
use Nette\Http\IResponse;
use Nette\InvalidArgumentException;
use Nette\Security\User;
use Varhall\Restino\Middlewares\Operations\AuthenticationMiddleware;
use Varhall\Restino\Middlewares\Operations\CorsMiddleware;
use Varhall\Restino\Middlewares\Operations\ExpandMiddleware;
use Varhall\Restino\Middlewares\Operations\FilterMiddleware;
use Varhall\Restino\Middlewares\Operations\IMiddleware;
use Varhall\Restino\Middlewares\Operations\RoleMiddleware;

class Factory
{
    protected Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function create(string $class): IMiddleware
    {
        $class = new \ReflectionClass($class);
        $arguments = [];

        $parameters = $class->getConstructor()?->getParameters() ?? [];
        foreach ($parameters as $parameter) {
            $type = $this->container->getByType($parameter->getType()?->getName() ?? '', false);

            if ($type) {
                $arguments[$parameter->getName()] = $type;
            }
        }

        $middleware = $class->newInstanceArgs($arguments);

        if (!($middleware instanceof IMiddleware)) {
            throw new InvalidArgumentException("{$class} is not subclass of " . IMiddleware::class);
        }

        return $middleware;
    }

    public function authentication(): AuthenticationMiddleware
    {
        return $this->create(AuthenticationMiddleware::class);
    }

    public function role(string $role): RoleMiddleware
    {
        return new RoleMiddleware($this->container->getByType(User::class), $role);
    }

    public function cors(array $options = []): CorsMiddleware
    {
        return new CorsMiddleware($this->container->getByType(IResponse::class), $options);
    }

    public function filter(array $options = []): FilterMiddleware
    {
        return new FilterMiddleware($this->container->getByType(IResponse::class));
    }

    public function expand(array $rules): ExpandMiddleware
    {
        return new ExpandMiddleware($rules);
    }
}