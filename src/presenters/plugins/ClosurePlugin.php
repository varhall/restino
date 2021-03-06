<?php

namespace Varhall\Restino\Presenters\Plugins;

use Varhall\Restino\Presenters\RestRequest;

class ClosurePlugin extends Plugin
{
    protected $closure = NULL;

    public function __construct(callable $closure)
    {
        $this->closure = $closure;
    }

    protected  function handle(RestRequest $request, ...$args)
    {
        return call_user_func_array($this->closure, [ $request ] + $args);
    }
}