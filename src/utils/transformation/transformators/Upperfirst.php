<?php

namespace Varhall\Rest\Utils\Transformation\Transformators;

/**
 * Description of Upperfirst
 *
 * @author sibrava
 */
class Upperfirst implements ITransformator
{
    public function apply($value)
    {
        if (is_string($value))
            return ucfirst($value);
        
        return $value;
    }
}
