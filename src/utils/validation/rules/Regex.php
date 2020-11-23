<?php

namespace Varhall\Restino\Utils\Validation\Rules;

class Regex extends Rule
{
    public function valid($value)
    {
        try {
            \Nette\Utils\Validators::assert($value, "pattern:{$this->arguments}");

        } catch (\Nette\Utils\AssertionException $ex) {
            return $ex->getMessage();
        }
    }
}
