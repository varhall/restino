<?php

namespace Varhall\Restino\Filters;

use Varhall\Restino\Results\IResult;

class Closure implements IFilter
{
    protected \Closure $callable;

    public function __construct(callable $callable)
    {
        $this->callable = \Closure::fromCallable($callable);
    }


    public function execute(Context $context, callable $next): IResult
    {
        return call_user_func($this->callable, $context, $next);
    }

}