<?php

namespace Varhall\Restino\Mapping\Mappers;


use Nette\Schema\Schema;
use Nette\Utils\DateTime;
use Nette\Utils\Validators;
use Varhall\Restino\Mapping\Target;

class Date implements IMapper
{
    use Scalar;

    protected $patterns = [
        '^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2}:\d{2}(\.\d+)?(([+\-]\d{2}:\d{2})|Z)?)?$'
    ];

    public function addPattern($pattern)
    {
        $this->patterns[] = $pattern;
    }

    public function apply(mixed $value): mixed
    {
        if ($date = $this->parse($value)) {
            return $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        }

        return $value;
    }

    protected function parse(mixed $value): ?DateTime
    {
        if (Validators::isNumericInt($value)) {
            return DateTime::from($value);
        }

        foreach ($this->patterns as $pattern) {
            if (preg_match("/{$pattern}/i", $value)) {
                return DateTime::from($value);
            }
        }

        return null;
    }

    public function schema(Target $target): Schema
    {
        return $this->scalarSchema($target, \DateTime::class);
    }
}

