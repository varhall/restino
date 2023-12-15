<?php

namespace Varhall\Restino\Middlewares\Attributes;

use Varhall\Restino\Middlewares\Factory;
use Varhall\Restino\Middlewares\Operations\IMiddleware;

interface IMiddlewareAttribute
{
    public function middleware(Factory $factory): IMiddleware;
}