<?php

namespace Varhall\Rest\Utils\Validation\Rules;

/**
 * Description of Regex
 *
 * @author sibrava
 */
class Regex implements IRule
{
    public function apply($value, $args)
    {
        \Nette\Utils\Validators::assert($value, 'pattern:' . $args);
    }
}
