<?php

namespace Varhall\Rest\Presenters\Plugins;

use Nette\Application\UI\Presenter;

/**
 * Abstract presenter plugin. Plugin is called during request process and transforms
 * the request.
 *
 * @author Ondrej Sibrava <sibrava@varhall.cz>
 */
abstract class Plugin
{    
    public function run(array &$data, Presenter $presenter, $method)
    {
        $result = $this->handle($data, $presenter, $method);
     
        if ($result && $result instanceof Results\IPluginResult)
            $result->run($presenter);
    }
    
    protected abstract function handle(array &$data, Presenter $request, $method);
    
    protected function terminate($response, $code = 500)
    {
        return new Results\Termination($response, $code);
    }
    
    protected function redirect($destination, $args = NULL)
    {
        return new Results\Redirection($destination, $args);
    }
}
