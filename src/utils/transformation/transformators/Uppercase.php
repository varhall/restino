<?php

namespace Varhall\Rest\Utils\Transformation\Transformators;

/**
 * Description of Uppercase
 *
 * @author sibrava
 */
class Uppercase implements ITransformator
{
    public function apply($value)
    {
        if (is_string($value))
            return strtoupper($value);
        
        return $value;
    }
}
