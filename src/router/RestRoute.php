<?php

namespace Varhall\Restino\Router;

/**
 * Description of RestRoute
 *
 * @author Ondrej Sibrava <sibrava@varhall.cz>
 */
class RestRoute extends AbstractRoute
{
    public function match(\Nette\Http\IRequest $httpRequest)
    {
        $request = parent::match($httpRequest);

        if ($request != NULL) {
            $action = 'default';
            $data = NULL;
            $params = $request->getParameters();

            switch ($httpRequest->getMethod()) {
                case 'GET':
                    $action = ($request->getParameter('id')) ? 'get' : 'list';
                    $data = $httpRequest->getQuery();
                    break;

                case 'POST':
                    $action = 'create';

                    // clone existing
                    $clone = $request->getParameter('clone');
                    if (!!$clone) {
                        $action = 'clone';
                        $params['id'] = $clone;
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
                    break;
            }

            $params['action'] = 'rest' . ucfirst(strtolower($action));

            if ($data)
                $params['data'] = isset($data['data']) ? $data['data'] : $data;

            // filter only valid keys
            $params = array_intersect_key($params, array_flip([ 'module', 'controller', 'action', 'id', 'data' ]));

            $request->setParameters($params);
        }

        return $request;
    }
}