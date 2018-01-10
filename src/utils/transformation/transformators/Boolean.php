<?php

namespace Varhall\Restino\Utils\Transformation\Transformators;

/**
 * Description of Boolean
 *
 * @author sibrava
 */
class Boolean implements ITransformator
{
    public function apply($value)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
