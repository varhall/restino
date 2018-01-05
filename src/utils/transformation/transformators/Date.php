<?php

namespace Varhall\Restino\Utils\Transformation\Transformators;

/**
 * Description of Date
 *
 * @author sibrava
 */
class Date implements ITransformator
{
    public function apply($value)
    {
        if (!is_string($value))
            return $value;
        
        if (\Nette\Utils\Validators::isNumeric($value))
            return $value;
        
        $time = strtotime($value);
        return $time ? \Nette\Utils\DateTime::from($value) : $value;
    }
}

