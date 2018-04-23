<?php

namespace Varhall\Restino\Presenters\Plugins;

use Varhall\Restino\Presenters\RestRequest;

/**
 * Description of UnsupportedMethodPlugin
 *
 * @author sibrava
 */
class AllowedMethodPlugin extends Plugin
{
    protected  function handle(RestRequest $request, ...$args)
    {
        $allowedMethods = $this->presenterCall($request->getPresenter(), 'methodsOnly');

        if (is_array($allowedMethods) && !empty($allowedMethods) && !in_array($request->method, $allowedMethods))
            return $this->terminate('Method is unsupported', \Nette\Http\Response::S405_METHOD_NOT_ALLOWED);

        return $request->next();
    }
}
