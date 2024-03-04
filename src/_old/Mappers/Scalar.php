<?php

namespace Varhall\Restino\OldMapping\Mappers;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Varhall\Restino\OldMapping\Target;

trait Scalar {

    protected function scalarSchema(Target $target, ?string $type = null): Schema
    {
        $rule = $target->getRule();
        return Expect::type($type ?? $rule?->getRule() ?? $target->getType()?->getName() ?? 'mixed')
            ->required($rule && $rule->getRequired());
    }

}