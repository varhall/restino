<?php

namespace Varhall\Restino\Utils\Validation\Rules;


use Varhall\Restino\Utils\Transformation\Transformator;

class System extends Rule
{
    public function toTransformationRule()
    {
        return Transformator::instance()->createRule($this->name);
    }

    public function valid($value)
    {
        try {
            \Nette\Utils\Validators::assert($value, trim("{$this->name}:$this->arguments", ':'));

        } catch (\Nette\Utils\AssertionException $ex) {
            return $ex->getMessage();
        }
    }
}