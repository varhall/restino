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
            $files = [];

            switch ($httpRequest->getMethod()) {
                case 'GET':
                    $action = ($request->getParameter('id')) ? 'get' : 'list';
                    //$data = $httpRequest->getQuery();
                    $data = [ 'status' => TRUE ];
                    break;
                
                case 'POST':
                    $action = 'create';
                    $data = json_decode(file_get_contents('php://input'), TRUE);
                    $files = $this->getBase64Files($data);
                    break;
                
                case 'PUT':
                case 'PATCH':
                    $action = 'update';
                    $data = json_decode(file_get_contents('php://input'), TRUE);
                    $files = $this->getBase64Files($data);
                    break;
                
                case 'DELETE':
                    $action = 'delete';
                    break;
            }
            
            $params = $request->getParameters();
            $params['action'] = 'rest' . ucfirst(strtolower($action));
            
            if (!empty($files)) {
                unset($data['files']);
                $params['files'] = $files;
            }
            
            if ($data)
                $params['data'] = isset($data['data']) ? $data['data'] : $data;

            // filter only valid keys
            $params = array_intersect_key($params, array_flip([ 'module', 'controller', 'action', 'id', 'data', 'files' ]));

            $request->setParameters($params);
        }
        
        return $request;
    } 
}