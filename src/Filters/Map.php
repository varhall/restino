<?php

namespace Varhall\Restino\Filters;

use Varhall\Restino\Results\AbstractResult;
use Varhall\Restino\Results\IResult;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Map implements IFilter
{
    protected string $class;

    public function __construct(string $class)
    {
        $this->class = $class;
    }

    public function execute(Context $context, callable $next): IResult
    {
        $result = $next($context);

        if ($result instanceof AbstractResult) {
            $result->addMapper(fn($item) => new $this->class($item));
        }

        return $result;
    }
}