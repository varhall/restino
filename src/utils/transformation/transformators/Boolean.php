<?php

namespace Varhall\Restino\Utils\Transformation\Transformators;


class Boolean extends Rule
{
    public function apply($value)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
