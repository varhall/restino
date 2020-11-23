<?php

namespace Varhall\Restino\Utils\Transformation\Transformators;


class Nothing extends Rule
{
    public function apply($value)
    {
        return $value;
    }
}
