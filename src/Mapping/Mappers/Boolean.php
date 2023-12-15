<?php

namespace Varhall\Restino\Mapping\Mappers;


use Nette\Schema\Schema;
use Varhall\Restino\Mapping\Target;

class Boolean implements IMapper
{
    use Scalar;

    public function apply(mixed $value): mixed
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function schema(Target $target): Schema
    {
        return $this->scalarSchema($target, 'bool');
    }
}
