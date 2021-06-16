<?php


namespace Varhall\Restino\Utils\Transformation\Transformators;


class Nil extends Rule
{
    public function apply($value)
    {
        return $value === 'null' || $value === 'nil' ? null : $value;
    }
}