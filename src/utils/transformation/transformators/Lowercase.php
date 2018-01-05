<?php

namespace Varhall\Restino\Utils\Transformation\Transformators;

/**
 * Description of Lowercase
 *
 * @author sibrava
 */
class Lowercase implements ITransformator
{
    public function apply($value)
    {
        if (is_string($value))
            return strtolower($value);
        
        return $value;
    }
}
