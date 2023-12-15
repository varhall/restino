<?php

namespace Varhall\Restino\Mapping\Mappers;


use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Varhall\Restino\Mapping\Target;

class Structure implements IMapper
{
    protected string $type;

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    public function apply(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        $reflection = new \ReflectionClass($this->type);
        $result = [];

        foreach ($reflection->getProperties() as $item) {
            $target = new Target($item);

            if (!array_key_exists($target->getName(), $value) && !$target->isRequired()) {
                continue;
            }

            $result[$target->getName()] = array_key_exists($target->getName(), $value)
                ? $target->getMapper()->apply($value[$target->getName()])
                : null;
        }

        return $result;
    }

    public function schema(Target $target): Schema
    {
        $type = $this->type;
        $reflection = new \ReflectionClass($type);
        $properties = [];

        foreach ($reflection->getProperties() as $prop) {
            $prop = new Target($prop);
            $properties[$prop->getName()] = $prop->getMapper()->schema($prop);
        }

        return Expect::from(new $type(), $properties)->skipDefaults();
    }
}
