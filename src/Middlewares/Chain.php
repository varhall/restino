<?php

namespace Varhall\Restino\Middlewares;

use Nette\InvalidArgumentException;
use Varhall\Restino\Controllers\RestRequest;
use Varhall\Restino\Middlewares\Operations\ClosureMiddleware;
use Varhall\Restino\Middlewares\Operations\IMiddleware;

class Chain
{
    protected array $middlewares = [];

    protected Factory $factory;

    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
    }

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function chain(callable $method, string $action): callable
    {
        foreach (array_reverse($this->middlewares) as $config) {
            if ($config->canRun($action)) {
                $middleware = $config->getMiddleware();
                $method = fn(RestRequest $request): mixed => $middleware($request, $method);
            }
        }

        return $method;
    }

    public function add(string $name, mixed $middleware): Configuration
    {
        if (array_key_exists($name, $this->middlewares)) {
            throw new InvalidArgumentException("Middleware with name {$name} is already registered");
        }

        if (is_callable($middleware) && !($middleware instanceof IMiddleware)) {
            $middleware = new ClosureMiddleware($middleware);
        }

        $this->middlewares[$name] = new Configuration(
            ($middleware instanceof IMiddleware) ? $middleware : $this->factory->create($middleware)
        );

        return $this->middlewares[$name];
    }

    public function get(string $name): Configuration
    {
        if (!array_key_exists($name, $this->middlewares)) {
            throw new InvalidArgumentException("Middleware with name {$name} is not registered");
        }

        return $this->middlewares[$name];
    }

    public function remove(string $name): void
    {
        if (!array_key_exists($name, $this->middlewares)) {
            throw new InvalidArgumentException("Middleware with name {$name} is not registered");
        }

        unset($this->middlewares[$name]);
    }
}