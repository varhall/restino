<?php

namespace Varhall\Restino\Presenters\Plugins;

use Varhall\Restino\Presenters\RestRequest;

/**
 * Filters request and removes the keys which are not in $allowed array
 *
 * @author Ondrej Sibrava <sibrava@varhall.cz>
 */
class FilterPlugin extends Plugin
{
    protected  function handle(RestRequest $request, ...$args)
    {
        $request->data = array_intersect_key($request->data, array_flip($this->presenterCall($request->getPresenter(), 'filterDefinition')));

        return $request->next();
    }

    /*protected function handle(array &$data, \Nette\Application\UI\Presenter $presenter)
    {
        $data = array_intersect_key($data, array_flip($this->presenterCall($presenter, 'filterDefinition')));
    }*/
}
