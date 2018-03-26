<?php

namespace Varhall\Restino\Presenters\Plugins;

use Varhall\Restino\Presenters\RestRequest;
use Varhall\Restino\Utils\Configuration;
use \Varhall\Restino\Utils\Transformation\Transformator;

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
            Configuration::extractSection($this->presenterCall($request->getPresenter(), 'transformDefinition'), $request->method),
            Configuration::extractSection($this->presenterCall($request->getPresenter(), 'validationDefinition'), $request->method)
        );

        foreach ($rules as $property => $options) {
            $rules[$property] = array_map(function($item) { return explode(':', $item)[0]; }, $options);

            foreach (Transformator::defaults() as $defopt) {
                array_unshift($rules[$property], $defopt);
            }
        }

        $request->data = Transformator::transformate($request->data, $rules, $request->method);

        return $request->next();
    }

    /*protected function handle(array &$data, \Nette\Application\UI\Presenter $presenter)
    {
        $rules = array_merge_recursive(
            Configuration::extractSection($this->presenterCall($presenter, 'transformDefinition'), $presenter->getMethod()),
            Configuration::extractSection($this->presenterCall($presenter, 'validationDefinition'), $presenter->getMethod())
        );

        foreach ($rules as $property => $options) {
            $rules[$property] = array_map(function($item) { return explode(':', $item)[0]; }, $options);

            foreach (Transformator::defaults() as $defopt) {
                array_unshift($rules[$property], $defopt);
            }
        }

        $data = Transformator::transformate($data, $rules, $presenter->getMethod());
    }*/
}
