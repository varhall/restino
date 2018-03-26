<?php

namespace Varhall\Restino\Presenters\Plugins;

use Varhall\Restino\Presenters\RestRequest;
use \Varhall\Restino\Utils\Validation\Validator;

/**
 * Validation plugin checks the given data
 *
 * @author Ondrej Sibrava <sibrava@varhall.cz>
 */
class ValidatePlugin extends Plugin
{
    protected function handle(RestRequest $request, ...$args)
    {
        $errors = Validator::validate($request->data, $this->presenterCall($request->getPresenter(), 'validationDefinition'), $request->method);

        if (count($errors) > 0)
            return $this->terminate(['errors' => $errors], \Nette\Http\Response::S400_BAD_REQUEST);

        return $request->next();
    }

    /*protected function handle(array &$data, \Nette\Application\UI\Presenter $presenter)
    {
        $errors = Validator::validate($data, $this->presenterCall($presenter, 'validationDefinition'), $presenter->getMethod());

        if (count($errors) > 0)
            return $this->terminate(['errors' => $errors], \Nette\Http\Response::S400_BAD_REQUEST);
    }*/
}
