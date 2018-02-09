<?php


namespace Varhall\Restino\Presenters;

use Varhall\Restino\Utils\FileUtils;


/**
 * Description of RestTrait
 *
 * @author fero
 */
trait RestTrait
{
    /**
     * Is AJAX request?
     * @return bool
     */
    public function isAjax()
    {
        return TRUE;
    }

    /**
     * Ziska z parametru ID kompozitni primarni klic, oddeleny znakem '-', vlozeny do klidu asociativniho pole
     * 
     * <b>priklad:</b><br>
     * id = 1-5<br>
     * names = ['user_id', 'role_id']<br>
     * <b>vystup:</b> [ 'user_id' => 1, 'role_id' => 5 ]<br>
     * 
     * @param array $names
     * @return type
     * @throws \Nette\InvalidArgumentException
     */
    protected function compositePrimaryKey(array $names)
    {
        $rawId = $this->getRequest()->getParameter('id');
        
        if (empty($rawId))
            throw new \Nette\InvalidArgumentException('ID parameter is empty or it does not exist');
        
        $parts = array_map('trim', explode('-', $rawId));
        
        if (count($parts) < count($names))
            throw new \Nette\InvalidArgumentException('Composite ID parameters count is less than ' . count($names));
        
        $composite = [];
        foreach ($names as $index => $key) {
            $composite[$key] = is_numeric($parts[$index]) ? intval($parts[$index]) : $parts[$index];
        }
        
        return $composite;
    }
    
    /**
     * Ziska vstupni data z pozadavku
     * 
     * @return array
     */
    protected function getRequestData()
    {
        return $this->getParameter('data', []);
    }

    /**
     * Ziska vstupni soubory z pozadavku
     *
     * @param $key Nazev klice pozadavku, kde se nachazi soubor(y)
     * @return array
     */
    protected function getRequestFiles($key = 'file')
    {
        $data = $this->getParameter('data', []);
        return FileUtils::retrieveFiles($data, $key);
    }
    
    // deprecated
    /**
     * Provede filtraci zdrojovych dat. Odstrani ze zdrojovych dat nedovolene klice
     * 
     * @param array $data
     * @param array $allowed
     * @return array
     */
    protected function filterRequestData(array $data, array $allowed)
    {
        $result = [];
        
        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                $result[$key] = $data[$key];
            }
        }
            
        return $result;
    }
    
    // deprecated
    /**
     * Provede validaci parametru v poli a v pripade chyby vrati JSON s chybou HTTP 400 Bad Request
     * validacni volby je mozne pouzit podle https://doc.nette.org/cs/2.4/validators
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
     * @param array $data
     * @param [ 'required', 'int:1..10', 'regex:^[a-z]+$' ]
     */
    protected function validateRequestData(array &$data, array $rules)
    {
        $errors = $this->performValidation($data, $rules);
        if (count($errors) > 0)
            $this->sendJsonError(['errors' => $errors], \Nette\Http\Response::S400_BAD_REQUEST);
    }
    
    /**
     * Provede validaci vstupnich dat
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
    private function performValidation(array &$data, array $rules)
    {
        $errors = [];
        foreach ($rules as $property => $propRules) {
            // split or wrap single rule to multiple rules
            if (is_string($propRules))
                $propRules = explode('|', $propRules);
            
            else if (is_array($propRules)) {
                $res = [];
                foreach ($propRules as $r)
                    if (is_string($r))
                        $res = array_merge($res, explode('|', $r));
                
                $propRules = $res;
            }
            
            $propRules = array_map('trim', $propRules);
            
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
        $customValidators = [
            'required'  => function($value, $expected) {
                // pravidlo neprovadi nic, protoze casto je potreba odeslat prazdnou hodnotu (0, '', false,...)
            },
            'regex'     => function($value, $expected) {
                \Nette\Utils\Validators::assert($value, 'pattern:' . $expected);
            },
            'date'      => function(&$value, $expected) {
                try {
                    $value = \Nette\Utils\DateTime::from(strtotime($value));
                    
                } catch (\Exception $ex) {
                    throw new \Nette\Utils\AssertionException('Value ' . $value . ' is not correct date');
                }
            },
            'enum'      => function($value, $expected) {
                $enum = array_map('trim', explode(',', $expected));
                if (!in_array($value, $enum))
                    throw new \Nette\Utils\AssertionException('Field not match enum [' . $expected . ']');
            }
        ];
        
        $transformers = [
            'int'       => function($value) { 
                return \Nette\Utils\Validators::isNumericInt($value) ? intval($value) : $value; 
            },
            'integer'   => function($value) { 
                return \Nette\Utils\Validators::isNumericInt($value) ? intval($value) : $value; 
            },
            'float'     => function($value) { 
                $value = str_replace(',', '.', $value);
                
                return \Nette\Utils\Validators::isNumeric($value) ? floatval($value) : $value; 
            },
            'number'    => function($value) { 
                $value = str_replace(',', '.', $value);

                
                if (\Nette\Utils\Validators::isNumericInt($value)) 
                    return intval($value);
                
                else if (\Nette\Utils\Validators::isNumeric($value))
                    return floatval($value);
                
                return $value;
            }
        ];
        
        foreach ($rules as $rule) {
            $parts = explode(':', $rule, 2);
            
            $type = $parts[0];
            $args = (count($parts) > 1) ? $parts[1] : NULL;

            // pokud existuje transformacni pravidlo, provede jej
            if (isset($transformers[$type]) && is_callable($transformers[$type]))
                $value = $transformers[$type]($value);
            
            // zkontroluje pole a v pripade chyby vyhodi vyjimku
            if (isset($customValidators[$type]) && is_callable($customValidators[$type])) {
                $customValidators[$type]($value, $args);

            } else {
                \Nette\Utils\Validators::assert($value, $rule);
            }
        }
    }
    
    
    /// RESPONSE UTILS
    
    /**
     * Odesle JSON response. Automaticky prevede objekty Selection a ActiveRow
     * 
     * <b>Volby</b><br>
     * 
     * <b>filter:</b> 
     * popis: zdroj filtrace vystupnich hodnot
     * priklad: 'query' / ['field = value', 'field &gt;= value'] / FALSE<br>
     * <br>
     * 
     * <b>filter_exclude:</b>
     * popis: vlastnosti, ktere maji byt vyjmuty z filtrace (zejmena pokud je zdroj QueryString)
     * priklad: ['property1', 'property2']<br>
     * <br>
     * 
     * <b>field_exclude:</b>
     * popis: odstrani vlastnost z vystupu
     * priklad: ['property1', 'property2']<br>
     * <br>
     * 
     * <b>field_expand:</b>
     * popis: provede expanzi podle pravidel definovanych v ExpandDefinition<br>
     *        zdrojem vlastnosti pro expanzi je v QueryString parametr 'expand'
     * priklad: ['property1', 'property2']<br>
     * <br>
     * 
     * @param mixed $data Odesilana data
     * @param array $options
     * @return type
     */
    public function sendJson($data, array $options = [])
    {
        if (empty($data))
            return parent::sendJson('');

        $pagination = NULL;

        // filter, order, paginate
        if ($data instanceof \Nette\Database\Table\Selection) {
            $this->filterResponse($data, $this->getJsonResponseOption($options, 'filter', 'query'), $this->getJsonResponseOption($options, 'filter_exclude', []));   
            $this->orderResponse($data);
            $pagination = $this->paginateResponse($data);
        }
        
        // build response
        $response = $this->toSendable($data);
        
        if (is_scalar($response))
            $response = [ 'message' => $data ];
  
        // expand
        $expand = $this->getJsonResponseOption($options, 'field_expand', []);
        if ($data instanceof \Nette\Database\Table\Selection) {
            $i = 0;
            foreach ($data as $item) {
                $response[$i] = $this->expandResponse($response[$i], $item, $expand);
                $i++;
            }
        } else if ($data instanceof \Nette\Database\Table\ActiveRow) {
            $response = $this->expandResponse($response, $data, $expand);
        }
        
        // exclude
        $this->excludeProperty($response, $this->getJsonResponseOption($options, 'field_exclude', []));

        if ($pagination) {
            $response = [
                'pagination'    => $pagination,
                'results'       => $response
            ];
        }

        return parent::sendJson($response);
    }
    
    private function getJsonResponseOption(array $options, $key, $default = NULL)
    {
        return isset($options[$key]) ? $options[$key] : $default;
    }
    
    /**
     * Odesle JSON s chybovym HTTP kodem. Automaticky provadi serializaci
     * 
     * @param type $data
     * @param type $errorCode
     */
    public function sendJsonError($data, $errorCode = \Nette\Http\Response::S500_INTERNAL_SERVER_ERROR)
    {
        $this->getHttpResponse()->setCode($errorCode);
        
        if (is_scalar($data))
            $data = [ 'message' => $data ];
        
        $this->sendJson($data);
    }
    
    /**
     * Prevede parametr na hodnotu odeslatelnou v JSON
     * 
     * @param mixed $data
     * @return string|array
     */
    private function toSendable($data)
    {
        if (is_array($data) || is_scalar($data))
            return $data;
        
        else if ($data instanceof \Varhall\Dbino\ISerializable)
            return $data->toArray();
        
        else if ($data instanceof \Nette\Database\Table\Selection)
            return $this->dataToArray($data);
        
        else if ($data instanceof \Nette\Database\Table\ActiveRow)
            return $data->toArray();
        
        else if (is_object($data))
            return json_decode(json_encode($data), true);
        
        return [];
    }
    
    /**
     * Prevede iterovatelnou kolekci (Selection) na pole
     * 
     * @param type $data
     * @return type
     */
    protected function dataToArray($data)
    {
        $result = [];
        
        foreach ($data as $item)
            $result[] = $item->toArray();
        
        return $result;
    }
    
    // property exclusion
    
    /**
     * Rekurzivne odstrani vlastnosti predaneho pole
     * 
     * @param array $data
     * @param array $exclude
     */
    private function excludeProperty(array &$data, array $exclude = [])
    {
        foreach ($data as $key => &$value) {
            if (is_string($key) && in_array($key, $exclude))
                unset($data[$key]);
            
            else if (is_array($value))
                $this->excludeProperty($value, $exclude);
        }
    }
    
    // expansion
    
    /**
     * Definice expanznich pravidel.
     * 
     * <b>Tvar expanznich pravidel:</b><br>
     * [<br>
     *      'role'          => function($user) { return $user->ref('roles', 'role_id); }<br>
     *      'role'          => 'ref:roles:role_id<br>
     *      'categories'    => 'related:categories:item_id'<br>
     * ]<br>
     * 
     * @todo Dodelat textove expanzni pravidlo
     * @return array Pole klicovane nazvem expandovane vlastnosti a hodnotou je expanzni pravidlo nebo funkce
     */
    protected function expandDefinition()
    {
        return [];
    }
    
    /**
     * Provede expanzi odpovedi
     * 
     * @param array $response Odesilana odpoved
     * @param array $source Originalni objekt, ktery je mozne expandovat
     * @param array $expandFields Sada expanznich pravidel, spojena se ziskanymi pravidly z QueryStringu
     * @return array Expandovana odpoved
     */
    private function expandResponse($response, $source, $expandFields)
    {
        $query = array_map('trim', explode(',', $this->getHttpRequest()->getQuery('expand', '')));
        $expandFields = array_merge((array) $expandFields, $query);

        $definitions = $this->expandDefinition();

        foreach ($expandFields as $field) {
            if (!isset($definitions[$field]))
                continue;
            
            $definition = $definitions[$field];
            
            if (is_string($definition))
                $response[$field] = $this->toSendable($this->expandByString($definition, $source));
            
            else if (is_callable($definition))
                $response[$field] = $this->toSendable(call_user_func($definition, $source, $this->getRequestData()));
        }
        
        return $response;
    }
    
    /**
     * Provede expanzi na zaklade zjednodusujiciho stringoveho pravidla
     * 
     * <b>Priklad retezcovych pravidel</b><br>
     * 'role'          => 'ref:roles:role_id<br>
     * 'categories'    => 'related:categories:item_id'<br>
     * 
     * @param type $definition
     * @param type $source
     * @return type
     * @throws \Nette\InvalidArgumentException
     */
    private function expandByString($definition, $source)
    {
        if ($source instanceof \Varhall\Dbino\Model && method_exists($source, $definition)) {
            return call_user_func([$source, $definition]);
        }
        
        list($rule, $table, $column) = array_pad(array_map('trim', explode(':', $definition)), 3, null);
        
        if ($rule == 'ref')
            return $source->ref($table, $column);
        
        else if ($rule == 'related')
            return $source->related($table, $column);
        
        else if ($rule == 'method' && method_exists($source, $table))
            return call_user_func_array([$source, $table], array_map('trim', explode(',', $column)));
            
        
        throw new \Nette\InvalidArgumentException("Expansion rule {$definition} is invalid");
    }
    
    // filtering
    
    /**
     * Provede filtraci objektu Selection na zaklade filtracnich parametru z QueryStringu
     * 
     * <b>Parametry:</b><br>
     * role=1 ... role = 1<br>
     * role&gt;1 ... role &gt; 1<br>
     * role&lt;1 ... role &lt; 1<br>
     * role&gt;=1 ... role &gt;= 1<br>
     * role&lt;=1 ... role &lt;= 1<br>
     * role&lt;&gt;1 ... role != 1<br>
     * role=1,2,3 ... role IN (1,2,3)<br>
     * name=pep* ... name LIKE pep%
     * 
     * @param \Nette\Database\Table\Selection $data
     * @return \Nette\Database\Table\Selection
     */
    protected function filterResponse(\Nette\Database\Table\Selection $data, $source = 'query', $exclude = [])
    {
        if (is_string($exclude))
            $exclude = array_map('trim', explode(',', $exclude));

        $parameters = $this->getFilterParameters($source, $exclude);

        // zpracovani specifickych hodnot
        for ($i = 0; $i < count($parameters); $i++) {
            $parameter = &$parameters[$i];
            
            if ($parameter['operator'] == '=' && ($parameter['value'] instanceof \DateTime || preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/i', $parameter['value']))) {
                $date = ($parameter['value'] instanceof \DateTime) ? $parameter['value']->format('Y-m-d') : $parameter['value'];
                $date = \Nette\Utils\DateTime::createFromFormat('Y-m-d', $date);
                
                // rozlozi datum na rozsah >= date a < date+1
                $parameters[] = [
                    'field'     => $parameter['field'],
                    'operator'  => '>=',
                    'value'     => $date->format('Y-m-d')
                ];
                
                $parameters[] = [
                    'field'     => $parameter['field'],
                    'operator'  => '<',
                    'value'     => $date->modifyClone('+1 day')->format('Y-m-d')
                ];
                
                unset($parameters[$i]);
                
            } else if ($parameter['value'] === 'null') {
                $parameter['value'] = NULL;
                $parameter['operator'] = ($parameter['operator'] == '!=') ? 'NOT' : '';
            }
        }

        foreach ($parameters as $param) { 
            $condition = empty($param['operator']) ? $param['field'] : "{$param['field']} {$param['operator']} ?";
            $data->where($condition, $param['value']);
        }

        $search = $this->getHttpRequest()->getQuery('search');
        if (!empty($search)) {
            $this->search($search, $data);
        }

        return $data;
    }

    protected function search($value, \Nette\Database\Table\Selection &$data)
    {
        if ($data instanceof \Varhall\Dbino\ISearchable)
            $data->search($value);
    }
    
    private function getFilterParameters($source = 'query', array $exclude = [])
    {
        $filter = [];
        
        // filter from automatic query string source
        if ($source === 'query') {
            foreach ($this->getHttpRequest()->getQuery() as $key => $value) {
                $filter[] = $key . (empty($value) ? '' : '=') . $value;
            }
            
            $exclude = array_merge($exclude, ['expand', 'order', 'limit', 'offset', 'search']);
        
        // filter from array source
        } else if (is_array($source)) {
            $filter = $source;
        }
        
        return $this->parseFilterParameters($filter, $exclude);
    }
    
    private function parseFilterParameters(array $filter, array $excluded = [])
    {
        $params = [];
        $operators = ['>=', '<=', '<>', '=', '>', '<'];
        
        foreach ($filter as $raw) {
            
            $parsed = FALSE;
            foreach ($operators as $operator) {
                $parts = explode($operator, $raw);
                if (count($parts) != 2)
                    continue;
                
                $field = trim($parts[0]);
                $value = trim($parts[1]);
                
                // pokud je parametr vylouceny, preskocit
                if (in_array($field, $excluded)) {
                    $parsed = TRUE;
                    break;
                }
                
                // zpracovat vycet hodnot
                $multi = array_map('trim', explode(',', $value));
                if (count($multi) > 1) {
                    $value = $multi;
                    $operator = NULL;
                }
                
                // operator LIKE
                if ($operator == '=' && preg_match('/\*/', $value)) {
                    $value = preg_replace('/\*+/', '%', $value);
                    $operator = 'LIKE';
                }
                
                $params[] = [
                    'field'     => $field,
                    'value'     => $value,
                    'operator'  => $operator
                ];
                
                $parsed = TRUE;
                break;
            }
            
            // zastavit kvuli operatorum = vs >= vs <=
            if ($parsed)
                continue;
        }

        return $params;
    }
    
    /**
     * Rozparsuje filtracni parametry z QueryStringu
     * 
     * @return array Pole filtracnich parametru ve tvaru [ ['field' => 'age', 'value' => 50, 'operator' => '>='] ]
     */
    private function getQueryFilterParameters()
    {
        $params = [];
        $operators = ['>=', '<=', '<>', '=', '>', '<'];
        $excluded = ['expand', 'order', 'limit', 'offset'];
        
        foreach ($this->getHttpRequest()->getQuery() as $key => $value) {
            $raw = $key . (empty($value) ? '' : '=') . $value;

            $parsed = FALSE;
            foreach ($operators as $operator) {
                $parts = explode($operator, $raw);
                if (count($parts) != 2)
                    continue;
                
                $field = trim($parts[0]);
                $value = trim($parts[1]);
                
                // pokud je systemovy operator, preskocit
                if (in_array($field, $excluded)) {
                    $parsed = TRUE;
                    break;
                }
                
                // zpracovat vycet hodnot
                $multi = array_map('trim', explode(',', $value));
                if (count($multi) > 1) {
                    $value = $multi;
                    $operator = NULL;
                }
                
                // operator LIKE
                if ($operator == '=' && preg_match('/\*/', $value)) {
                    $value = preg_replace('/\*+/', '%', $value);
                    $operator = 'LIKE';
                }
                
                $params[] = [
                    'field'     => $field,
                    'value'     => $value,
                    'operator'  => $operator
                ];
                
                $parsed = TRUE;
                break;
            }
            
            // zastavit kvuli operatorum = vs >= vs <=
            if ($parsed)
                continue;
        }

        return $params;
    }
    
    // order & pagination
    
    /**
     * Provede razeni objektu Selection na zaklade parametru 'order' z QueryStringu
     * 
     * <b>Parametry v QueryStringu:</b><br>
     * order=age ... seradi podle veku vzestupne<br>
     * order=-age ... seradi podle veku sestupne<br>
     * order=age,name ... seradi nejprve podle veku a pak podle jmena, oboji vzestupne<br>
     * order=age,-name ... seradi nejprve podle veku vzestupne a pak podle jmena sestupne<br>
     * 
     * @param \Nette\Database\Table\Selection $data
     * @return \Nette\Database\Table\Selection
     */
    protected function orderResponse(\Nette\Database\Table\Selection $data)
    {
        $parameters = $this->getOrderParameters();
        
        foreach ($parameters as $parameter) {
            $order = $parameter['desc'] ? 'DESC' : 'ASC';
            $data->order("{$parameter['field']} {$order}");
        }
        
        return $data;
    }
    
    /**
     * Rozparsuje radici parametry z QueryStringu
     * 
     * @return array Pole radicich parametru ve tvaru [ ['field' => 'age', 'desc' => false] ]
     */
    private function getOrderParameters()
    {
        $params = [];
        $query = $this->getHttpRequest()->getQuery('order');
        
        if (empty($query))
            return [];
        
        $query = array_map('trim', explode(',', $query));
        
        foreach ($query as $q) {
            $desc = FALSE;
            if (substr($q, 0, 1) == '-')
                 $desc = TRUE;
            
            $params[] = [
                'field' => $desc ? substr($q, 1) : $q,
                'desc'  => $desc
            ];
        }
        
        return $params;
    }
    
    /**
     * Provede strankovani objektu Selection na zaklade parametru v QueryStringu
     * 
     * @param \Nette\Database\Table\Selection $data
     */
    protected function paginateResponse(\Nette\Database\Table\Selection $data)
    {
        $limit = $this->getHttpRequest()->getQuery('limit');
        $offset = $this->getHttpRequest()->getQuery('offset');
        $total = NULL;

        if (!!$limit && \Nette\Utils\Validators::isNumericInt($limit)) {
            $fullData = clone $data;

            $data->limit($limit, $offset);
            $total = $fullData->count();

            return [
                'limit'         => intval($limit),
                'offset'        => [
                    'current'       => intval($offset),
                    'next'          => $limit && $offset < $total - $limit
                                            ? ($offset + $limit)
                                            : NULL,
                    'previous'      => $limit && $offset >= $limit
                                            ? $offset - $limit
                                            : NULL,
                ],
                'total'         => $total,
            ];
        }

        return NULL;
    }
}
