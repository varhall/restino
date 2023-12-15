<?php

namespace Varhall\Restino\Mapping\Mappers;


use Nette\Schema\Schema;
use Varhall\Restino\Mapping\Target;

class Nothing implements IMapper
{
    use Scalar;

    public function apply(mixed $value): mixed
    {
        return $value;
    }

    public function schema(Target $target): Schema
    {
        return $this->scalarSchema($target);
    }
}
