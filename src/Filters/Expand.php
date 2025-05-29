<?php

namespace Varhall\Restino\Filters;

use Varhall\Restino\Controllers\RestRequest;
use Varhall\Restino\Results\AbstractResult;
use Varhall\Restino\Results\IResult;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Expand implements IFilter
{
    const QUERY_PARAMETER = '_expand';

    protected array $rules;

    public function __construct(array $rules)
    {
        foreach ($rules as $property => $rule) {
            if (is_int($property) && is_string($rule)) {    // allow simplified rule ['prop' => 'prop'] as ['prop']
                $rules[$rule] = $rule;
                unset($rules[$property]);
            }
        }

        $this->rules = $rules;
    }


    public function execute(Context $context, callable $next): IResult
    {
        $result = $next($context);

        if ($result instanceof AbstractResult) {
            $query = $this->requestedRules($context->getRequest());
            $result->addMapper(fn($result, $item) => $this->map($result, $item, $query));
        }

        return $result;
    }

    public function getRules(): array
    {
        return $this->rules;
    }

    protected function map(mixed $result, mixed $object, array $query): mixed
    {
        if (!is_array($result)) {
            return $result;
        }

        foreach ($query as $property) {
            if (array_key_exists($property, $this->rules)) {
                $result[$property] = $this->expand($object, $this->rules[$property]);
            }
        }

        return $result;
    }

    protected function expand(mixed $object, string|callable $rule): mixed
    {
        if (is_callable($rule)) {
            return $rule($object);
        }

        if (is_string($rule) && is_object($object) && method_exists($object, $rule)) {
            return $object->$rule();
        }

        return null;
    }

    protected function requestedRules(RestRequest $request): array
    {
        $expand = $request->getParameter(self::QUERY_PARAMETER, '');
        return array_map('trim', explode(',', $expand));
    }
}