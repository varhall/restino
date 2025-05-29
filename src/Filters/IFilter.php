<?php

namespace Varhall\Restino\Filters;

use Varhall\Restino\Results\IResult;

interface IFilter
{
    public function execute(Context $context, callable $next): IResult;
}