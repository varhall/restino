<?php

namespace Varhall\Restino\OldMapping\Mappers;


use Nette\Schema\Schema;
use Varhall\Restino\OldMapping\Target;

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
