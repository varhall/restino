<?php

namespace Varhall\Restino\Utils\Transformation\Transformators;


class Lowercase extends Rule
{
    public function apply($value)
    {
        if (is_string($value))
            return strtolower($value);
        
        return $value;
    }
}
