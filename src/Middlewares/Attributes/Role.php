<?php

namespace Varhall\Restino\Middlewares\Attributes;

use Varhall\Restino\Middlewares\Factory;
use Varhall\Restino\Middlewares\Operations\IMiddleware;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Role implements IMiddlewareAttribute
{
    protected string $role;

    public function __construct(string $role)
    {
        $this->role = $role;
    }

    public function middleware(Factory $factory): IMiddleware
    {
        return $factory->role($this->role);
    }
}