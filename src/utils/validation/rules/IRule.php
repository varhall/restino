<?php

namespace Varhall\Rest\Utils\Validation\Rules;

/**
 * Description of IRule
 *
 * @author sibrava
 */
interface IRule
{
    public function apply($value, $args);
}
