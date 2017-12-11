<?php

namespace Varhall\Rest\Presenters\Plugins;

/**
 * Description of UnsupportedMethodPlugin
 *
 * @author sibrava
 */
class UnsupportedMethodPlugin extends Plugin
{
    protected function handle(array &$data, \Nette\Application\UI\Presenter $request, $method)
    {
        $this->terminate('Method is unsupported', \Nette\Http\Response::S405_METHOD_NOT_ALLOWED);
    }
}
