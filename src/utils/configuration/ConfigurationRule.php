<?php

namespace Varhall\Restino\Utils\Configuration;


class ConfigurationRule
{
    public $name        = null;
    public $arguments   = null;
    public $modifiers   = [];

    public function __construct($name, $arguments = null, $modifiers = [])
    {
        $this->name = $name;
        $this->arguments = $arguments;
        $this->modifiers = $modifiers;
    }

    public static function fromString($string)
    {
        $modifiers = array_filter([
            'only' => Configuration::getParameter($string, 'only')
        ]);

        foreach (array_keys($modifiers) as $option) {
            $string = Configuration::removeParameter($string, $option);
        }

        $parts = array_map('trim', explode(':', $string));
        $name = $parts[0];
        $arguments = count($parts) > 1 ? $parts[1] : null;

        return new static($name, $arguments, $modifiers);
    }

    public static function fromRule(ConfigurationRule $rule)
    {
        return new static($rule->name, $rule->arguments, $rule->modifiers);
    }

    public function allowed($method)
    {
        return empty($this->modifiers) || in_array($method, $this->modifiers);
    }
}