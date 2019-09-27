<?php

namespace Varhall\Restino\Presenters;


class Definitions
{
    public static function enum(array $values)
    {
        return 'enum:' . implode(',', $values);
    }
}