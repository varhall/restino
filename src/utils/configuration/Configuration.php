<?php

namespace Varhall\Restino\Utils\Configuration;


class Configuration
{
    public static function create($rules, $section, IConfigured $source)
    {
        foreach ($rules as $property => $propRules) {
            if ($section && is_array($propRules) && array_key_exists($section, $propRules))
                $propRules = $propRules[$section];

            $propRules = array_map(function($rule) use ($source) { return $source->createRule($rule); }, !is_array($propRules) ? [ $propRules ] : $propRules);
            $propRules = array_filter($propRules, function($rule) use ($section) { return $rule->allowed($section); });

            $rules[$property] = $propRules;
        }

        return $rules;
    }

    public static function hasParameter($rule, $parameter)
    {
        return self::getParameter($rule, $parameter) !== null;
    }
    
    public static function getParameter($rule, $parameter)
    {
        $matches = [];
        preg_match("/{$parameter}=([^:=]*)/i", $rule, $matches);

        if (!empty($matches) && count($matches) > 1)
            return trim($matches[1]);

        return null;
    }
    
    public static function removeParameter($rule, $parameter)
    {
        return preg_replace("/:?{$parameter}=[^:=]*/i", '', $rule);
    }
}
