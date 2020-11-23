<?php

namespace Varhall\Restino\Utils\Transformation\Transformators;

use Varhall\Restino\Utils\Configuration\ConfigurationRule;

abstract class Rule extends ConfigurationRule
{
    public abstract function apply($value);
}
