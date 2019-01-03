<?php

namespace Varhall\Restino\Presenters\Results;

use Nette\Application\Responses\JsonResponse;
use Nette\InvalidStateException;
use Varhall\Restino\Presenters\RestRequest;
use Varhall\Utilino\Collections\ISearchable;

class Json implements IResult
{
    public $filterExclude = [];

    public $fieldExclude = [];

    public $expand = [];

    /**
     * @var RestRequest
     */
    protected $request = NULL;

    protected $data = NULL;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function run(RestRequest $request)
    {
        $this->request = $request;

        return new JsonResponse($this->createJson($this->data));
    }

    /**
     * Sends JSON response. Converts automatically objects Selection and ActiveRow
     *
     * @param mixed $data Sent data
     * @param array $options
     * @return type
     */
    protected function createJson($data)
    {
        if (empty($data))
            return [];

        $pagination = NULL;

        // filter, order
        if ($data instanceof \Nette\Database\Table\Selection) {
            $this->filterResponse($data);
            $this->orderResponse($data);
        }

        // search
        if ($data instanceof \Varhall\Utilino\Collections\ISearchable) {
            $this->search($data);
        }

        // paginate
        if ($data instanceof \Varhall\Utilino\Collections\IPaginable || $data instanceof \Nette\Database\Table\Selection) {
            $pagination = $this->paginateResponse($data);
        }

        // build response
        $response = $this->toSendable($data);

        if (is_scalar($response))
            $response = [ 'message' => $data ];

        // expand
        if ($data instanceof \Nette\Database\Table\Selection) {
            $i = 0;
            foreach ($data as $item) {
                $response[$i] = $this->expandResponse($response[$i], $item);
                $i++;
            }
        } else if ($data instanceof \Nette\Database\Table\ActiveRow) {
            $response = $this->expandResponse($response, $data);
        }

        // exclude
        $this->excludeProperty($response, $this->fieldExclude);

        if ($pagination) {
            $response = [
                'pagination'    => $pagination,
                'results'       => $response
            ];
        }

        return $response;
    }

    /**
     * Converts a value sendable as JSON
     *
     * @param mixed $data
     * @return string|array
     */
    private function toSendable($data)
    {
        if (is_array($data) || is_scalar($data) || is_null($data))
            return $data;

        else if ($data instanceof \Varhall\Utilino\ISerializable)
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
     * Converts Selection to array
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
     * Removes recursively properties from given array
     *
     * @param array $data
     * @param array $exclude
     */
    protected function excludeProperty(array &$data, array $exclude = [])
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
     * Provede expanzi odpovedi
     * Does an object expansion
     *
     * @param array $response Data to be returned
     * @param array $source Original object which can be expanded
     * @param array $expandFields Fields to be expanded merged together with QueryString defined rules
     * @return array Data with expanded fields
     */
    protected function expandResponse($response, $source)
    {
        $query = array_map('trim', explode(',', $this->getQueryArgument('expand', '')));
        $expandFields = array_merge((array) $this->expand, $query);

        $definitions = $this->expandDefinition();

        foreach ($expandFields as $field) {
            if (!isset($definitions[$field]))
                continue;

            $definition = $definitions[$field];

            if (is_string($definition))
                $response[$field] = $this->toSendable($this->expandByString($definition, $source));

            else if (is_callable($definition))
                $response[$field] = $this->toSendable(call_user_func($definition, $source));
        }

        return $response;
    }

    protected function expandDefinition()
    {
        if (!method_exists($this->request->getPresenter(), 'expandDefinition'))
            return [];

        $r = new \ReflectionMethod(get_class($this->request->getPresenter()), 'expandDefinition');
        $r->setAccessible(TRUE);
        return $r->invokeArgs($this->request->getPresenter(), []);
    }

    /**
     * Does an expansion by simplified string rule
     *
     * <b>Priklad retezcovych pravidel</b><br>
     * 'role'          => 'ref:roles:role_id<br>
     * 'categories'    => 'related:categories:item_id'<br>
     * 'customer'      => 'method:customer'<br>
     * 'customer'      => 'customer'    // for dbino only<br>
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
     * Does a result filtration of using the rules defined in QueryString
     *
     * <b>Parameters:</b><br>
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
    protected function filterResponse(\Nette\Database\Table\Selection $data)
    {
        $parameters = $this->getFilterParameters($data);

        // check valid columns
        $validColumns = method_exists($data, 'columns') ? $data->columns() : NULL;
        $parameters = array_filter($parameters, function($parameter) use ($validColumns) { return $validColumns === NULL || in_array($parameter['field'], $validColumns); });

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

        $parameters = array_filter($parameters);

        foreach ($parameters as $param) {
            $condition = empty($param['operator']) ? $param['field'] : "{$param['field']} {$param['operator']} ?";
            $data->where($condition, $param['value']);
        }

        return $data;
    }

    protected function getFilterParameters(\Nette\Database\Table\Selection $data)
    {
        $filter = [];

        foreach ($this->getQueryArgument() as $key => $value) {
            $filter[] = $key . (empty($value) && $value !== '0' ? '' : '=') . $value;
        }


        $exclude = array_merge($this->filterExclude, ['expand', 'order', 'limit', 'offset', 'search']);

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

    protected function search(&$data)
    {
        $value = $this->getQueryArgument('search');

        if (!empty($value)) {
            $data = $data->search($value);
        }
    }

    // order & pagination

    /**
     * Provede razeni objektu Selection na zaklade parametru 'order' z QueryStringu
     * Does Selection object order by QueryString 'order' parameter
     *
     * <b>QueryString parameters:</b><br>
     * order=age ... order by age ascending<br>
     * order=-age ... order by age descending<br>
     * order=age,name ... order by age as first then by name (both ascending)<br>
     * order=age,-name ... order ascedning by age as first, then descending by name<br>
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
     * Parses order arguments from QueryString
     *
     * @return array Array of order arguments [ ['field' => 'age', 'desc' => false] ]
     */
    private function getOrderParameters()
    {
        $params = [];
        $query = $this->getQueryArgument('order');

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
     * Does Selection pagination by QueryString parameter
     *
     * @param \Nette\Database\Table\Selection $data
     */
    protected function paginateResponse(&$data)
    {
        $limit = $this->getQueryArgument('limit');
        $offset = $this->getQueryArgument('offset');
        $total = NULL;

        if (!!$limit && \Nette\Utils\Validators::isNumericInt($limit)) {
            $fullData = clone $data;

            $data = $data->limit($limit, $offset);
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

    protected function getQueryArgument($argument = NULL)
    {
        if (!$this->request)
            throw new InvalidStateException('Request has not been set yet');

        $httpRequest = $this->request->getPresenter()->getHttpRequest();
        return call_user_func_array([ $httpRequest, 'getQuery' ], func_get_args());
    }
}