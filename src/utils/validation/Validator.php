<?php

namespace Varhall\Restino\Utils\Validation;

use Varhall\Restino\Utils\Configuration\Configuration;
use Varhall\Restino\Utils\Configuration\ConfigurationRule;
use Varhall\Restino\Utils\Configuration\IConfigured;
use Varhall\Restino\Utils\Validation\Rules\Date;
use Varhall\Restino\Utils\Validation\Rules\Enum;
use Varhall\Restino\Utils\Validation\Rules\Regex;
use Varhall\Restino\Utils\Validation\Rules\Required;
use Varhall\Restino\Utils\Validation\Rules\Rule;
use Varhall\Restino\Utils\Validation\Rules\System;

class Validator implements IConfigured
{
    protected $rules = [];

    public static function instance()
    {
        return new static();
    }

    public function __construct()
    {
        $this->rules = [
            'required'  => Required::class,
            'regex'     => Regex::class,
            'date'      => Date::class,
            'datetime'  => Date::class,
            'enum'      => Enum::class
        ];
    }



    //////////////////////////////// VALIDATION ////////////////////////////////


    /**
     * Performs input data validation. Validation options can be from https://doc.nette.org/cs/3.0/validators
     *
     * <b>addional rules:</b>
     *  - required  = field must be included and cannot be empty<br>
     *  - regex     = pattern alias<br>
     *  - date      = datetime
     *  - datetime  = date alias
     *  - enum      = set of allowed values
     *
     * <b>rules format:</b><br>
     * [<br>
     *      field_1 => 'string',<br>
     *      field_2 => 'string:1..100',<br>
     *      field_3 => [ 'string', 'required' ],<br>
     *      field_4 => [ 'string:1..100, 'required:only=create' ]<br>
     *      field_5 => [ Enum::create([ 'foo', 'bar' ]) ],
     *      field_6 => [ Enum::create([ 'foo', 'bar' ], [ 'only' => 'create' ]) ]
     * ]<br>
     *
     * @param array $data Input data
     * @param array $rules Rules array
     * @return array Asociativni pole chyb ve tvaru [nazev_pole => 'chyba']
     */
    public function validate(array $data, array $rules, $section = null)
    {
        $errors = [];
        $rules = $this->configuration($rules, $section);

        foreach ($rules as $property => $propRules) {
            $error = $this->validateProperty($property, $data, $propRules);

            if (!empty($error))
                $errors[$property] = $error;
        }

        return $errors;
    }

    public function configuration($rules, $section)
    {
        return Configuration::create($rules, $section, $this);
    }

    public function createRule($rule)
    {
        if ($rule instanceof Rule)
            return $rule;

        if (is_string($rule))
            $rule = ConfigurationRule::fromString($rule);

        $class = isset($this->rules[$rule->name]) ? $this->rules[$rule->name] : System::class;
        return $class::fromRule($rule);
    }

    protected function validateProperty($property, array $data, $rules)
    {
        // validate required if not present
        if (!array_key_exists($property, $data) || $data[$property] === null)
            return $this->validateRequired($property, $data, $rules);

        // validate rules
        foreach ($rules as $rule) {
            if ($rule instanceof Required)
                continue;

            $rule->property = $property;
            $rule->data = $data;

            $result = $rule->valid($data[$property]);

            if (is_string($result))
                $result = new Error($result);

            if ($result instanceof Error)
                return $result->setSource($rule);
        }

        return null;
    }

    protected function validateRequired($property, array $data, $rules)
    {
        foreach ($rules as $rule) {
            if ($rule instanceof Required) {
                return (new Error($rule->valid(false)))->setSource($rule);
            }
        }

        return null;
    }
}
