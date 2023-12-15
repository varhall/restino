<?php

namespace Varhall\Restino\Mapping;

use Nette\Utils\Type;
use Varhall\Restino\Mapping\Mappers\IMapper;
use Varhall\Restino\Mapping\Mappers\Nothing;
use Varhall\Restino\Mapping\Mappers as M;

/**
 * @method string getName()
 * @method \ReflectionNamedType getType()
 * @method bool hasType()
 * @method \ReflectionAttribute[] getAttributes(string $type = null)
 */
class Target
{
    protected array $mappers;

    protected \ReflectionProperty|\ReflectionParameter $object;

    public function __construct(\ReflectionProperty|\ReflectionParameter $object)
    {
        $this->object = $object;

        $this->mappers = [
            'number'        => M\Number::class,
            'int'           => M\Number::class,
            'integer'       => M\Number::class,
            'double'        => M\Number::class,
            'float'         => M\Number::class,

            'bool'          => M\Boolean::class,
            'boolean'       => M\Boolean::class,

            'date'          => M\Date::class,
            'datetime'      => M\Date::class,
        ];
    }

    public function __call(string $name, array $arguments)
    {
        return call_user_func_array([ $this->object, $name ], $arguments);
    }

    public function isBuiltinType(): bool
    {
        if (!$this->object->hasType()) {
            return false;
        }

        $type = Type::fromString($this->object->getType()->getName());
        return $type->isBuiltin();
    }

    public function isDateType(): bool
    {
        if (!$this->object->hasType()) {
            return false;
        }

        $type = $this->object->getType()->getName();
        return class_exists($type) && ($type === \DateTime::class || is_subclass_of($type, \DateTime::class));
    }

    public function isClassType(): bool
    {
        if (!$this->object->getType()) {
            return false;
        }

        if (!class_exists($this->object->getType()->getName())) {
            return false;
        }

        if ($this->isDateType()) {
            return false;
        }

        return true;
    }

    public function getClassName(): string|null
    {
        return $this->isClassType() ? $this->object->getType()->getName() : null;
    }

    public function getRule(): Rule|null
    {
        $rules = $this->object->getAttributes(Rule::class);
        $attribute = array_shift($rules);

        if ($attribute) {
            return $attribute->newInstance();
        }

        return null;
    }

    public function isRequired(): bool
    {
        if ($rule = $this->getRule()) {
            return $rule->getRequired();
        }

        return $this->object->getType() && $this->object->getType()->allowsNull();
    }

    public function getMapper(): IMapper
    {
        $rule = $this->getRule();
        $mapper = new Nothing();

        if ($rule) {
            $class = $this->mappers[$rule->getBaseRule()] ?? Nothing::class;
            $mapper = new $class();

        } else if ($this->isDateType()) {
            $mapper = new M\Date();

        } else if ($this->isBuiltinType()) {
            $type = Type::fromString($this->object->getType()->getName())->getSingleName();

            if (array_key_exists($type, $this->mappers)) {
                $class = $this->mappers[$type];
                $mapper = new $class();
            }
        } else if ($this->isClassType()) {
            $mapper = new M\Structure($this->getClassName());
        }

        if (!$this->hasType() || $this->getType()->allowsNull()) {
            $mapper = new M\Nil($mapper);
        }

        return $mapper;
    }
}