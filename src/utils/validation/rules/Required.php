<?php

namespace Varhall\Restino\Utils\Validation\Rules;

class Required extends Rule
{
    public function valid($value)
    {
        if (empty($value) && $value !== false)
            return 'Field is required';
    }
}