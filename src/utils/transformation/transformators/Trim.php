<?php

namespace Varhall\Restino\Utils\Transformation\Transformators;


class Trim extends Rule
{
    public function apply($value)
    {
        if (is_string($value))
            return trim($value);
        
        return $value;
    }
}
