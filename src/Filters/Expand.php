<?php

namespace Varhall\Restino\Filters;

use Varhall\Restino\Controllers\RestRequest;
use Varhall\Restino\Results\AbstractResult;
use Varhall\Restino\Results\IExpandable;
use Varhall\Restino\Results\IResult;
use Varhall\Restino\Results\Serializer;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Expand implements IFilter
{
    const QUERY_PARAMETER = '_expand';

    public function execute(Context $context, callable $next): IResult
    {
        $result = $next($context);

        if ($result instanceof AbstractResult) {
            $query = $this->requestedRules($context->getRequest());
            $result->addMapper(function($item) use ($query, $result) {
                if (!$item instanceof IExpandable) {
                    return $item;
                }

                // serializer of the result is resolved lazily - it is assigned by Dispatcher after filters
                return $this->map($item, $query, $result->getSerializer());
            });
        }

        return $result;
    }

    /**
     * Serializes the object and adds requested expansions. Expanded values are returned unserialized,
     * they are serialized (and downgraded) by the result serializer as standalone values.
     */
    public function map(IExpandable $object, array $query, ?Serializer $serializer = null): mixed
    {
        $rules = $object->expansions();

        foreach ($rules as $property => $rule) {
            if (is_int($property) && is_string($rule)) {    // allow simplified rule ['prop' => 'prop'] as ['prop']
                $rules[$rule] = $rule;
                unset($rules[$property]);
            }
        }

        $serializer ??= new Serializer();
        $result = $serializer->serialize($object);

        foreach ($query as $property) {
            if (is_array($result) && array_key_exists($property, $rules)) {
                $result[$property] = $this->expand($object, $rules[$property]);
            }
        }

        return $result;
    }

    public function expand(mixed $object, string|callable $rule): mixed
    {
        if (is_callable($rule)) {
            return $rule($object);
        }

        if (is_object($object) && method_exists($object, $rule)) {
            return $object->$rule();
        }

        return null;
    }

    public function requestedRules(RestRequest $request): array
    {
        $expand = $request->getParameter(self::QUERY_PARAMETER, '');
        return array_map('trim', explode(',', $expand));
    }
}