<?php

namespace Varhall\Restino\Router;

use Nette\Routing\Route;
use Varhall\Utilino\Utils\Reflection;

class RestRoute extends Route
{
    public function match(\Nette\Http\IRequest $httpRequest): ?array
    {
        Reflection::writePrivateProperty($this, 'mask', 'api/<presenter>[/<id>]/more');
        $meta = array_merge($this->getMetadata(), ['action' => 'more']);
        Reflection::writePrivateProperty($this, 'metadata', Reflection::callPrivateMethod($this, 'normalizeMetadata', [$meta]));

        $request = parent::match($httpRequest);
        dumpe($request);

        if (is_array($request)) {
            $action = 'default';
            $data = [];
            $method = $httpRequest->getMethod();

            if ($method === 'GET') {
                $id = $request['id'] ?? null;
                $action = $id ? 'get' : 'index';
                $data = $httpRequest->getQuery();

            } else if ($method === 'POST') {
                $action = 'create';
                $data = json_decode($httpRequest->getRawBody(), true);

            } else if ($method === 'PUT' || $method === 'PATCH') {
                $action = 'update';
                $data = json_decode($httpRequest->getRawBody(), true);

            } else if ($method === 'DELETE') {
                $action = 'delete';
                $data = $httpRequest->getQuery();
            }

            $request['action'] = strtolower($action);
            $request['presenter'] = ucfirst(strtolower($request['presenter']));
            $request['data'] = ($data ?? []) + array_diff_key(array_filter($request), array_flip([ 'module', 'presenter', 'action' ]));

            // filter only valid keys
            $request = array_intersect_key($request, array_flip([ 'module', 'presenter', 'action', 'data' ]));
        }

        return $request;
    }
}