<?php

namespace Varhall\Restino\Utils\Validation\Rules;

/**
 * Description of Enum
 *
 * @author sibrava
 */
class Enum implements IRule
{
    public function apply($value, $args)
    {
        $enum = array_map('trim', explode(',', $args));
        
        if (!in_array($value, $enum))
            throw new \Nette\Utils\AssertionException('Field not match enum [' . $args . ']');
    }
}