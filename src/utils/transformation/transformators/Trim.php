<?php

namespace Varhall\Restino\Utils\Transformation\Transformators;

/**
 * Description of Text
 *
 * @author sibrava
 */
class Trim implements ITransformator
{
    public function apply($value)
    {
        if (is_string($value))
            return trim($value);
        
        return $value;
    }
}
