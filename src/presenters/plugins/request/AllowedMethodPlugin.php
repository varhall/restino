<?php

namespace Varhall\Restino\Presenters\Plugins\Request;

use Varhall\Restino\Presenters\Plugins\Plugin;
use Varhall\Restino\Presenters\RestRequest;
use Varhall\Restino\Presenters\Results\Termination;

class AllowedMethodPlugin extends Plugin
{
    /** @var array|mixed */
    protected $methods = [];

    public function __construct(array|callable $methods)
    {
        if (is_callable($methods)) {
            $methods = $methods();
        }

        $this->methods = $methods;
    }

    public function __invoke(RestRequest $request, callable $next): mixed
    {
        if (!empty($this->methods) && !in_array($request->method, $this->methods)) {
            return new Termination('Method is unsupported', \Nette\Http\Response::S405_METHOD_NOT_ALLOWED);
        }

        return $next($request);
    }
}
