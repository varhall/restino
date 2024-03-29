<?php

namespace Varhall\Restino\Middlewares\Operations;

use Nette\Database\Table\Selection;
use Varhall\Restino\Controllers\RestRequest;
use Varhall\Restino\Results\CollectionResult;
use Varhall\Restino\Results\IResult;
use Varhall\Utilino\Collections\ICollection;

class CollectionMiddleware implements IMiddleware
{
    const QUERY_LIMIT   = '_limit';
    const QUERY_OFFSET  = '_offset';
    const QUERY_ORDER   = '_order';

    public function __invoke(RestRequest $request, callable $next): IResult
    {
        $result = $next($request);

        if ($result->getData() instanceof ICollection || $result->getData() instanceof Selection) {
            $result = CollectionResult::fromResult($result);

            $this->order($result, $request);
            $this->paginate($result, $request);
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
            $result->addOrder($parameter->field, $parameter->desc);
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