<?php

namespace Varhall\Restino\OldMapping\Mappers;

use Nette\Schema\Schema;
use Varhall\Restino\OldMapping\Target;

interface IMapper
{
    public function apply(mixed $value): mixed;

    public function schema(Target $target): Schema;
}
