<?php

namespace Varhall\Restino\Utils\Transformation;

use Varhall\Restino\Utils\Configuration;

/**
 * Description of Transformator
 *
 * @author sibrava
 */
class Transformator
{
    private static $instance = NULL;
    
    private $transformators = NULL;
    
    private $defaults       = [ 'trim', 'number', 'date' ];
    
    // singleton factory method
    public static function __callStatic($name, $arguments)
    {
        if (!self::$instance)
            self::$instance = new static();
        
        return call_user_func_array([self::$instance, $name], $arguments);
    }
    
    private function __construct()
    {
        $this->transformators = [
            'trim'          => new Transformators\Trim(),
            'number'        => new Transformators\Number(),
            'date'          => new Transformators\Date(),
            'uppercase'     => new Transformators\Uppercase(),
            'lowercase'     => new Transformators\Lowercase(),
            'upperfirst'    => new Transformators\Upperfirst(),
        ];
    }
    
    public function addTransformator($name, Transformators\ITransformator $transformate)
    {
        $this->transformators[$name] = $transformate;
    }     
    
    
    
    //////////////////////////////// Transformation ////////////////////////////////
    
    private function transformate(array $data, array $rules, $section = NULL)
    {
        if (!empty($section))
            $rules = Configuration::extractSection($rules, $section);
        
        return $this->transformateData($data, $rules);
    }
    
    private function transformateData(array $data, array $rules)
    {
        foreach ($data as $key => $value) {
            $rule = isset($rules[$key]) ? $rules[$key] : $this->defaults;
            
            $data[$key] = $this->transformField($value, $rule);
        }
        
        return $data;
    }
    
    private function transformField($value, array $rules)
    {
        foreach ($rules as $rule) {
            if (isset($this->transformators[$rule]))
                $value = $this->transformators[$rule]->apply($value);
        }
        
        return $value;
    }
}
