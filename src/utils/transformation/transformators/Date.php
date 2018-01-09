<?php

namespace Varhall\Restino\Utils\Transformation\Transformators;

/**
 * Description of Date
 *
 * @author sibrava
 */
class Date implements ITransformator
{
    private $patterns = [
        '^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2}:\d{2}([+\-]\d{2}:\d{2})?)?$'
    ];

    public function addPattern($pattern)
    {
        $this->patterns[] = $pattern;
    }

    public function apply($value)
    {
        if (!is_string($value))
            return $value;
        
        if (\Nette\Utils\Validators::isNumeric($value))
            return $value;

        foreach ($this->patterns as $pattern) {
            if (preg_match("/{$pattern}/i", $value)) {
                return \Nette\Utils\DateTime::from($value);
            }
        }

        return $value;
    }
}

