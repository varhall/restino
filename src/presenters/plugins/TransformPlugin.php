<?php

namespace Varhall\Restino\Presenters\Plugins;

use \Varhall\Restino\Utils\Transformation\Transformator;

/**
 * Transform plugin converts input data to correct data types e.g. numeric strings
 * to numbers, string dates to date times, etc.
 *
 * @author Ondrej Sibrava <sibrava@varhall.cz>
 */
class TransformPlugin extends Plugin
{
    protected $rules = [];
    
    public function __construct(array $rules)
    {
        $this->rules = $rules;
    }
    
    protected function handle(array &$data, \Nette\Application\UI\Presenter $presenter, $method)
    {
        $data = Transformator::transformate($data, $this->rules, $method);
    }
}
