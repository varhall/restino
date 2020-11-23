<?php

namespace Varhall\Restino\Utils\Validation\Rules;

class Required extends Rule
{
    public function valid($exists)
    {
        return !$exists ? 'Field is required' : null;
    }
}