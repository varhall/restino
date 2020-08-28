<?php

namespace Varhall\Restino\Router;

/**
 * Description of RestRoute
 *
 * @author Ondrej Sibrava <sibrava@varhall.cz>
 */
class RestRoute extends AbstractRoute
{
    public function match(\Nette\Http\IRequest $httpRequest): ?array
    {
        $request = parent::match($httpRequest);

        if (!!$request) {
            $action = 'default';
            $data = [];
            //$params = $request->getParameters();

            switch ($httpRequest->getMethod()) {
                case 'GET':
                    $action = array_key_exists('id', $request) && !!$request['id'] ? 'get' : 'list';
                    $data = $httpRequest->getQuery();
                    break;

                case 'POST':
                    $action = 'create';

                    // clone existing
                    if (!!array_key_exists('clone', $request)) {
                        $action = 'clone';
                        $request['id'] = $request['clone'];
                        unset($request['clone']);
                    }

                    $data = json_decode(file_get_contents('php://input'), TRUE);
                    break;

                case 'PUT':
                case 'PATCH':
                    $action = 'update';
                    $data = json_decode(file_get_contents('php://input'), TRUE);
                    break;

                case 'DELETE':
                    $action = 'delete';
                    $data = $httpRequest->getQuery();
                    break;

                case 'OPTIONS':
                    header('Access-Control-Allow-Origin: *');
                    header('Access-Control-Allow-Headers: ' . join(',', [
                            'Content-Type',
                            'Authorization'
                        ]));
                    header('Access-Control-Allow-Methods:' . join(',', [
                            'GET',
                            'POST',
                            'PUT',
                            'DELETE'
                        ]));
                    exit;

                    break;
            }

            $request['action'] = 'rest' . ucfirst(strtolower($action));

            if (empty($data))
                $data = [];

            $request['data'] = isset($data['request_data']) ? $data['request_data'] : $data;

            // filter only valid keys
            $request = array_intersect_key($request, array_flip([ 'module', 'presenter', 'action', 'id', 'data' ]));
        }

        return $request;
    }
}