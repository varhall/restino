<?php

namespace Varhall\Restino\Utils\Transformation\Transformators;


class Upperfirst extends Rule
{
    public function apply($value)
    {
        if (is_string($value))
            return ucfirst($value);
        
        return $value;
    }
}
