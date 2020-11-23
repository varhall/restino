<?php

namespace Varhall\Restino\Utils\Validation\Rules;

class Date extends Rule
{
    public function toTransformationRule()
    {
        return \Varhall\Restino\Utils\Transformation\Transformators\Date::fromRule($this);
    }

    public function valid($value)
    {
        if ($value instanceof \DateTime)
            return true;

        try {
            \Nette\Utils\DateTime::from(strtotime($value));
            return true;

        } catch (\Exception $ex) {
            return "Value {$value} is not correct date";
        }
    }
}