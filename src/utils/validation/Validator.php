<?php

namespace Varhall\Restino\Utils\Validation;

use Varhall\Restino\Utils\Configuration;

/**
 * Description of Validator
 *
 * @author sibrava
 */
class Validator
{
    private static $instance = NULL;

    private $rules = NULL;

    // singleton factory method
    public static function __callStatic($name, $arguments)
    {
        if (!self::$instance)
            self::$instance = new static();

        return call_user_func_array([self::$instance, $name], $arguments);
    }

    private function __construct()
    {
        $this->rules = [
            'required'  => new Rules\Required(),
            'regex'     => new Rules\Regex(),
            'date'      => new Rules\Date(),
            'datetime'  => new Rules\Date(),
            'enum'      => new Rules\Enum()
        ];
    }

    public function addRule($name, Rules\IRule $rule)
    {
        $this->rules[$name] = $rule;
    }



    //////////////////////////////// VALIDATION ////////////////////////////////


    /**
     * Provede validaci vstupnich dat. Jako validacni volby je mozne pouzit podle https://doc.nette.org/cs/2.4/validators
     *
     * <b>pridana pravidla:</b>
     *  - required  = pole musi byt obsazeno a nesmi byt prazdne<br>
     *  - regex     = stejne jako pattern<br>
     *
     * <b>tvar pravidel:</b><br>
     * [<br>
     *      nazev_pole_1 => 'pravidlo',<br>
     *      nazev_pole_2 => 'pravidlo1|pravidlo2',<br>
     *      nazev_pole_3 => ['pravidlo1', 'pravidlo2'],<br>
     *      nazev_pole_4 => ['pravidlo1, 'pravidlo2|pravidlo3']<br>
     * ]<br>
     *
     * @param array $data Vstupni data
     * @param array $rules Pole pravidel v pozadovanem tvaru
     * @return array Asociativni pole chyb ve tvaru [nazev_pole => 'chyba']
     */
    private function validate(array $data, array $rules, $section = NULL)
    {
        if (!empty($section))
            $rules = Configuration::extractSection($rules, $section);

        return $this->validateData($data, $rules);
    }

    private function validateData(array &$data, array $rules)
    {
        $errors = [];
        foreach ($rules as $property => $propRules) {
            $propRules = Configuration::splitRule($propRules);

            // ignore property if isn't in source and add error if is required
            if (!isset($data[$property]) && in_array('required', $propRules))
                $errors[$property] = 'Field is required';

            if (!isset($data[$property]))
                continue;

            // process each rule
            try {
                $this->validateField($data[$property], $propRules);

            } catch (\Nette\Utils\AssertionException $ex) {
                $errors[$property] = $ex->getMessage();
            }
        }

        return $errors;
    }

    /**
     * Provede kontrolu hodnoty a v pripade chyby vyhodi vyjimku
     *
     * @param mixed $value Kontrolovana hodnota
     * @param array $rules Rozparsovane pole pravidel
     * @throws \Nette\Utils\AssertionException V pripade, ze hodnota nevyhovuje nejakemu pravidlu
     */
    private function validateField(&$value, array $rules)
    {
        foreach ($rules as $rule) {
            $parts = explode(':', $rule, 2);

            $type = $parts[0];
            $args = (count($parts) > 1) ? $parts[1] : NULL;

            // zkontroluje pole a v pripade chyby vyhodi vyjimku
            if (isset($this->rules[$type])) {
                $this->rules[$type]->apply($value, $args);

            } else {
                \Nette\Utils\Validators::assert($value, $rule);
            }
        }
    }
}
