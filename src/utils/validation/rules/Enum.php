<?php

namespace Varhall\Restino\Utils\Validation\Rules;


class Enum extends Rule
{
    public static function create($args, $modifiers = [])
    {
        return new static('enum', $args, $modifiers);
    }

    public function valid($value)
    {
        if (is_string($this->arguments))
            $this->arguments = array_map('trim', explode(',', $this->arguments));

        if (!is_array($this->arguments))
            return 'Enum is not valid array';

        if (!in_array($value, $this->arguments))
            return 'Field not match enum [' . implode(', ', $this->arguments) . ']';

        return true;
    }
}