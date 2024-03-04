<?php

namespace Varhall\Restino\OldMapping\Mappers;


use Nette\Schema\Elements\Type;
use Nette\Schema\Schema;
use Varhall\Restino\OldMapping\Target;

class Nil implements IMapper
{
    protected IMapper $mapper;

    public function __construct(IMapper $mapper)
    {
        $this->mapper = $mapper;
    }

    public function apply(mixed $value): mixed
    {
        return $value === 'null' || $value === 'nil'
            ? null
            : $this->mapper->apply($value);
    }

    public function schema(Target $target): Schema
    {
        $schema = $this->mapper->schema($target);

        if ($schema instanceof Type) {
            $schema = $schema->nullable();
        }

        return $schema;
    }
}