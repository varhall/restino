<?php

namespace Varhall\Restino\Mapping\Mappers;

use Nette\Schema\Schema;
use Varhall\Restino\Mapping\Target;

interface IMapper
{
    public function apply(mixed $value): mixed;

    public function schema(Target $target): Schema;
}
