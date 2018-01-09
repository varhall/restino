<?php

namespace Varhall\Restino\Presenters\Plugins;

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
    protected $transformRules = [];

    protected $validationRules = [];
    
    public function __construct(array $transformRules, array $validationRules = [])
    {
        $this->transformRules = $transformRules;
        $this->validationRules = $validationRules;
    }
    
    protected function handle(array &$data, \Nette\Application\UI\Presenter $presenter, $method)
    {
        $rules = array_merge_recursive(
            Configuration::extractSection($this->transformRules, $method),
            Configuration::extractSection($this->validationRules, $method)
        );

        foreach ($rules as $property => $options) {
            $rules[$property] = array_map(function($item) { return explode(':', $item)[0]; }, $options);

            foreach (Transformator::defaults() as $defopt) {
                array_unshift($rules[$property], $defopt);
            }
        }

        $data = Transformator::transformate($data, $rules, $method);
    }
}
