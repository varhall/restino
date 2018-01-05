<?php

namespace Varhall\Restino\Utils;


/**
 * Description of Configuration
 *
 * @author sibrava
 */
class Configuration
{
    public static function extractSection($rules, $section)
    {
        $result = array_map(function($rule) use ($section) {
            return (is_array($rule) && isset($rule[$section])) ? $rule[$section] : $rule;
        }, $rules);
        
        foreach ($result as $rKey => $rValue) {
            $result[$rKey] = array_filter((array)$rValue, function($item) use ($section) {
                $only = self::getParameter($item, 'only');

                return ($only === NULL || preg_match("/{$section}/i", $only));
            });
            
            $result[$rKey] = self::removeParameter($result[$rKey], 'only');
        }
        
        return $result;
    }
    
    public static function hasParameter($rule, $parameter)
    {
        return self::getParameter($rule, $parameter) !== NULL;
    }
    
    public static function getParameter($rule, $parameter)
    {
        foreach ((array) $rule as $item) {
            $matches = [];
            preg_match("/{$parameter}=([^:=]*)/i", $item, $matches);
            
            if (!empty($matches) && count($matches) > 1)
                return trim($matches[1]);
        }
        
        return NULL;
    }
    
    private static function removeParameter($rule, $parameter)
    {
        $rule = (array) $rule;
        
        foreach ($rule as $key => $item) {
            $rule[$key] = preg_replace("/:?{$parameter}=[^:=]*/i", '', $item);
        }
        
        return $rule;
    }
    
    public static function splitRule($rule)
    {
        // split or wrap single rule to multiple rules
        if (is_string($rule))
            $rule = explode('|', $rule);

        else if (is_array($rule)) {
            $res = [];
            foreach ($rule as $r)
                if (is_string($r))
                    $res = array_merge($res, explode('|', $r));

            $rule = $res;
        }

        return array_map('trim', $rule);
    }
}
