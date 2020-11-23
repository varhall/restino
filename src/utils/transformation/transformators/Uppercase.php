<?php

namespace Varhall\Restino\Utils\Transformation\Transformators;


class Uppercase extends Rule
{
    public function apply($value)
    {
        if (is_string($value))
            return strtoupper($value);
        
        return $value;
    }
}
