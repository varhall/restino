<?php

namespace Varhall\Restino\Utils\Validation\Rules;

use Varhall\Restino\Utils\Configuration\ConfigurationRule;
use Varhall\Restino\Utils\Transformation\Transformators\Nothing;

abstract class Rule extends ConfigurationRule
{
    public $property    = null;
    public $data        = null;

    public function toTransformationRule()
    {
        return Nothing::fromRule($this);
    }

    public abstract function valid($value);
}