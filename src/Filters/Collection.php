<?php

namespace Varhall\Restino\Filters;

use Varhall\Restino\Controllers\RestRequest;
use Varhall\Restino\Results\CollectionResult;
use Varhall\Restino\Results\IResult;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Collection implements IFilter
{
    const QUERY_LIMIT   = '_limit';
    const QUERY_OFFSET  = '_offset';
    const QUERY_ORDER   = '_order';


    public function execute(Context $context, callable $next): IResult
    {
        $result = $next($context);

        if ($result instanceof CollectionResult) {
            $this->order($result, $context->getRequest());
            $this->paginate($result, $context->getRequest());
        }

        return $result;
    }

    protected function paginate(CollectionResult $result, RestRequest $request): void
    {
        $result->paginate(
            $request->getParameter(self::QUERY_LIMIT),
            $request->getParameter(self::QUERY_OFFSET)
        );
    }

    protected function order(CollectionResult $result, RestRequest $request): void
    {
        foreach ($this->getOrderParameters($request) as $parameter) {
            $result->order($parameter->field, $parameter->desc);
        }
    }

    private function getOrderParameters(RestRequest $request): array
    {
        $order = $request->getParameter(self::QUERY_ORDER, '');
        $order = array_filter(explode(',', $order));

        if (empty($order)) {
            return [];
        }

        return array_map(fn($x) => $this->parseOrderParameter($x), $order);
    }

    private function parseOrderParameter(string $parameter): object
    {
        $parameter = trim($parameter);
        $desc = substr($parameter, 0, 1) === '-';

        return (object) [
            'field' => $desc ? substr($parameter, 1) : $parameter,
            'desc'  => $desc
        ];
    }
}