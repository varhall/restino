<?php

namespace Varhall\Restino\Utils\Transformation\Transformators;


class Number extends Rule
{
    public function apply($value)
    {
        if (is_string($value) && preg_match('/^-?[0-9]+([.,][0-9]+)?$/', $value))
            $value = str_replace(',', '.', $value);

        if (\Nette\Utils\Validators::isNumericInt($value)) 
            return intval($value);

        else if (\Nette\Utils\Validators::isNumeric($value))
            return floatval($value);

        return $value;
    }
}
