<?php

namespace Varhall\Rest\Presenters\Plugins;

use \Varhall\Rest\Utils\Validation\Validator;

/**
 * Validation plugin checks the given data 
 *
 * @author Ondrej Sibrava <sibrava@varhall.cz>
 */
class ValidatePlugin extends Plugin
{
    protected $rules = [];
    
    public function __construct(array $rules)
    {
        $this->rules = $rules;
    }
    
    protected function handle(array &$data, \Nette\Application\UI\Presenter $presenter, $method)
    {
        $errors = Validator::validate($data, $this->rules, $method);
        
        if (count($errors) > 0)
            return $this->terminate(['errors' => $errors], \Nette\Http\Response::S400_BAD_REQUEST);
    }
}
