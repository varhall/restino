<?php

namespace Varhall\Restino\Filters;

use Nette\Database\Table\Selection;
use Nette\Http\IRequest;
use Varhall\Dbino\Collections\Collection;
use Varhall\Restino\Results\IResult;
use Varhall\Restino\Filters\Collection as FCollection;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Filter implements IFilter
{
    public function execute(Context $context, callable $next): IResult
    {
        $result = $next($context);

        if ($result->getData() instanceof Selection || $result->getData() instanceof Collection) {
            $this->filter($result->getData(), $context->getContainer()->getByType(IRequest::class));
        }

        return $result;
    }

    protected function filter(Selection|Collection $data, IRequest $request): Selection|Collection
    {
        $parameters = $this->getFilterParameters($request);
        $parameters = $this->filterExistingColumns($parameters);


        // proces values
        for ($i = 0; $i < count($parameters); $i++) {
            $parameter = &$parameters[$i];

            if ($parameter['operator'] == '=' && ($parameter['value'] instanceof \DateTime || preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/i', $parameter['value']))) {
                $date = ($parameter['value'] instanceof \DateTime) ? $parameter['value']->format('Y-m-d') : $parameter['value'];
                $date = \Nette\Utils\DateTime::createFromFormat('Y-m-d', $date);

                // split date to range >= date and < date + 1
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
                $parameter['value'] = null;
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

    protected function getFilterParameters(IRequest $request): array
    {
        $filter = [];

        foreach ($request->getQuery() as $key => $value) {
            $filter[] = $key . (empty($value) && $value !== '0' ? '' : '=') . $value;
        }

        $exclude = [
            Expand::QUERY_PARAMETER,
            FCollection::QUERY_OFFSET,
            FCollection::QUERY_LIMIT,
            FCollection::QUERY_ORDER,
        ];

        return $this->parseFilterParameters($filter, $exclude);
    }

    private function parseFilterParameters(array $filter, array $excluded = []): array
    {
        $params = [];
        $operators = ['>=', '<=', '<>', '!=', '=', '>', '<'];

        foreach ($filter as $raw) {

            $parsed = false;
            foreach ($operators as $operator) {
                $parts = explode($operator, $raw);
                if (count($parts) != 2)
                    continue;

                $field = trim($parts[0]);
                $value = trim($parts[1]);

                // skip if parameter is excluded
                if (in_array($field, $excluded)) {
                    $parsed = true;
                    break;
                }

                // parse multi-values
                $multi = array_map('trim', explode(',', $value));
                if (count($multi) > 1) {
                    $value = $multi;
                    $operator = $operator === '!=' || $operator === '<>' ? 'NOT' : null;
                }

                // operator LIKE
                if ($operator == '=' && preg_match('/\*/', $value)) {
                    $value = preg_replace('/\*+/', '%', $value);
                    $operator = 'LIKE';
                }

                if ($operator === '!=')
                    $operator = '<>';

                $params[] = [
                    'field'     => $field,
                    'value'     => $value,
                    'operator'  => $operator
                ];

                $parsed = true;
                break;
            }

            // break due to operators = vs >= vs <=
            if ($parsed) {
                continue;
            }
        }

        return $params;
    }

    protected function filterExistingColumns(array $parameters): array
    {
        // check valid columns
        /*
        $validColumns = null;
        if (method_exists($this->request->getPresenter(), 'modelClass')) {
            $r = new \ReflectionMethod(get_class($this->request->getPresenter()), 'modelClass');
            $r->setAccessible(true);
            $model = $r->invokeArgs($this->request->getPresenter(), []);

            $validColumns = $model::columns();
        }

        $parameters = array_values(array_filter($parameters, function($parameter) use ($validColumns) {
            return $validColumns === null || in_array($parameter['field'], $validColumns);
        }));
        */

        return $parameters;
    }
}