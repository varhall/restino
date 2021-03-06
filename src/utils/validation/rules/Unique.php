<?php

namespace Varhall\Restino\Utils\Validation\Rules;

class Unique extends Rule
{
    public static function create($class, $modifiers = [])
    {
        return new Unique('unique', $class, $modifiers);
    }

    public function valid($value)
    {
        $class = $this->arguments;
        $count = $class::where($this->property, $value)->count();

        if ($count)
            return "Value {$value} is not unique";

        return true;
    }
}