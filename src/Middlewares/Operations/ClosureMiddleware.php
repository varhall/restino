<?php

namespace Varhall\Restino\Middlewares\Operations;

use Varhall\Restino\Controllers\RestRequest;
use Varhall\Restino\Results\IResult;

class ClosureMiddleware implements IMiddleware
{
    protected $callable;

    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    public function __invoke(RestRequest $request, callable $next): IResult
    {
        return call_user_func($this->callable, $request, $next);
    }
}