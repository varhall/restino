<?php

namespace Varhall\Restino\Utils\Validation\Rules;

/**
 * Description of Date
 *
 * @author sibrava
 */
class Date implements IRule
{
    public function apply($value, $args)
    {
        try {
            \Nette\Utils\DateTime::from(strtotime($value));
            
        } catch (\Exception $ex) {
            throw new \Nette\Utils\AssertionException('Value ' . $value . ' is not correct date');
        }
    }
}