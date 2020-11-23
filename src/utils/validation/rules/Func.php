<?php

namespace Varhall\Restino\Utils\Validation\Rules;


class Func extends Rule
{
    public function valid($value)
    {
        return call_user_func($this->func, $value);
    }
}