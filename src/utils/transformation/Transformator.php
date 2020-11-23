<?php

namespace Varhall\Restino\Utils\Transformation;

use Varhall\Restino\Utils\Configuration\Configuration;
use Varhall\Restino\Utils\Configuration\ConfigurationRule;
use Varhall\Restino\Utils\Configuration\IConfigured;
use Varhall\Restino\Utils\Transformation\Transformators\Nothing;
use Varhall\Restino\Utils\Transformation\Transformators\Rule;

class Transformator implements IConfigured
{
    protected $transformators = null;

    protected $defaults       = [ 'trim', 'number', 'date' ];

    public static function instance()
    {
        return new static();
    }

    public function __construct()
    {
        $this->transformators = [
            'trim'          => Transformators\Trim::class,
            'uppercase'     => Transformators\Uppercase::class,
            'lowercase'     => Transformators\Lowercase::class,
            'upperfirst'    => Transformators\Upperfirst::class,

            'number'        => Transformators\Number::class,
            'int'           => Transformators\Number::class,
            'integer'       => Transformators\Number::class,
            'double'        => Transformators\Number::class,
            'float'         => Transformators\Number::class,

            'bool'          => Transformators\Boolean::class,
            'boolean'       => Transformators\Boolean::class,

            'date'          => Transformators\Date::class,
            'datetime'      => Transformators\Date::class,
        ];
    }

    public function defaults()
    {
        return [ $this->createRule('trim') ];
    }

    //////////////////////////////// Transformation ////////////////////////////////

    public function transformate(array $data, array $rules, $section = null)
    {
        $configuration = $this->configuration($rules, $section);

        foreach ($data as $key => $value) {
            $rule = isset($configuration[$key]) ? $configuration[$key] : $this->defaults();

            $data[$key] = $this->transformField($value, $rule);
        }

        return $data;
    }

    public function configuration($rules, $section)
    {
        return Configuration::create($rules, $section, $this);
    }

    public function createRule($rule)
    {
        if ($rule instanceof Rule)
            return $rule;

        if ($rule instanceof \Varhall\Restino\Utils\Validation\Rules\Rule)
            return $rule->toTransformationRule();

        if (is_string($rule))
            $rule = ConfigurationRule::fromString($rule);

        $class = isset($this->transformators[$rule->name]) ? $this->transformators[$rule->name] : Nothing::class;
        return $class::fromRule($rule);
    }

    private function transformField($value, array $rules)
    {
        foreach ($rules as $rule) {
            $value = $rule->apply($value);
        }

        return $value;
    }
}
