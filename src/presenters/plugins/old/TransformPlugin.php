<?php

namespace Varhall\Restino\Presenters\Plugins;

use Varhall\Restino\Presenters\RestRequest;
use Varhall\Restino\Utils\Configuration\Configuration;
use \Varhall\Restino\Utils\Transformation\Transformator;
use Varhall\Restino\Utils\Validation\Validator;

/**
 * Transform plugin converts input data to correct data types e.g. numeric strings
 * to numbers, string dates to date times, etc.
 *
 * @author Ondrej Sibrava <sibrava@varhall.cz>
 */
class TransformPlugin extends Plugin
{
    protected  function handle(RestRequest $request, ...$args)
    {
        $rules = array_merge_recursive(
            Configuration::create($this->presenterCall($request->getPresenter(), 'transformDefinition'), $request->method, Transformator::instance()),
            Configuration::create($this->presenterCall($request->getPresenter(), 'validationDefinition'), $request->method, Validator::instance())
        );

        foreach ($rules as $property => $options) {
            foreach (Transformator::instance()->defaults() as $defopt) {
                array_unshift($rules[$property], $defopt);
            }
        }

        $request->data = Transformator::instance()->transformate($request->data, $rules, $request->method);

        return $request->next();
    }
}
