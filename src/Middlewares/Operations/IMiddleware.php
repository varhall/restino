<?php

namespace Varhall\Restino\Middlewares\Operations;

use Varhall\Restino\Controllers\RestRequest;
use Varhall\Restino\Results\IResult;

interface IMiddleware
{
    public function __invoke(RestRequest $request, callable $next): IResult;
}