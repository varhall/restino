<?php

namespace Varhall\Restino\Middlewares\Attributes;

use Varhall\Restino\Middlewares\Factory;
use Varhall\Restino\Middlewares\Operations\IMiddleware;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Secured implements IMiddlewareAttribute
{
    public function middleware(Factory $factory): IMiddleware
    {
        return $factory->authentication();
    }
}