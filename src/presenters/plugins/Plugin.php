<?php

namespace Varhall\Restino\Presenters\Plugins;

use Nette\Application\UI\Presenter;
use Nette\InvalidArgumentException;
use Varhall\Restino\Presenters\RestRequest;

/**
 * Abstract presenter plugin. Plugin is called during request process and transforms
 * the request.
 *
 * @author Ondrej Sibrava <sibrava@varhall.cz>
 */
abstract class Plugin
{
    public function run(RestRequest $request, ...$args)
    {
        $result = call_user_func_array([$this, 'handle'], array_merge([$request, $args]));

        if ($result && $result instanceof Results\IPluginResult)
            return $result->run($request->getPresenter());

        return $result;
    }

    protected abstract function handle(RestRequest $request, ...$args);
    
    protected function terminate($response, $code = 500)
    {
        return new Results\Termination($response, $code);
    }
    
    protected function redirect($destination, $args = NULL)
    {
        return new Results\Redirection($destination, $args);
    }

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
    }
}
