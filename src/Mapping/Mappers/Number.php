<?php

namespace Varhall\Restino\Mapping\Mappers;


use Nette\Schema\Schema;
use Varhall\Restino\Mapping\Target;

class Number implements IMapper
{
    use Scalar;

    public function apply(mixed $value): mixed
    {
        if (is_string($value) && preg_match('/^-?[0-9]+([.,][0-9]+)?$/', $value)) {
            $value = str_replace(',', '.', $value);
        }

        if (\Nette\Utils\Validators::isNumericInt($value)) {
            return intval($value);

        } else if (\Nette\Utils\Validators::isNumeric($value)) {
            return floatval($value);
        }

        return $value;
    }

    public function schema(Target $target): Schema
    {
        return $this->scalarSchema($target, 'number');
    }
}
