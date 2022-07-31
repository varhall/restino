<?php

namespace Varhall\Restino\Presenters\Plugins;

use Varhall\Restino\Presenters\RestRequest;
use Varhall\Restino\Presenters\Results\Redirection;
use Varhall\Restino\Presenters\Results\Termination;

abstract class Plugin
{
    public abstract function __invoke(RestRequest $request, callable $next): mixed;

    protected function terminate($response, $code = 500)
    {
        return new Termination($response, $code);
    }

    protected function redirect($destination, $args = null)
    {
        return new Redirection($destination, $args);
    }

    /*
    protected function checkPresenterRequirements(Presenter $presenter)
    {
        $classes = array_merge([get_class($presenter)], class_parents($presenter));

        foreach ($classes as $class) {
            if (in_array('Varhall\Restino\Presenters\RestPresenter', class_uses($class)))
                return TRUE;
        }

        return FALSE;
    }

    protected function presenterCall($presenter, $method, array $args = [])
    {
        $r = new \ReflectionMethod(get_class($presenter), $method);
        $r->setAccessible(TRUE);
        return $r->invokeArgs($presenter, $args);
    }*/
}
