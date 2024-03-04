<?php

namespace Varhall\Restino\OldMapping\Mappers;


use Nette\Schema\Schema;
use Varhall\Restino\OldMapping\Target;

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
