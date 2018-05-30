<?php

namespace Varhall\Restino\Router;

use Varhall\Restino\Utils\FileUtils;

/**
 * Description of RestRoute
 *
 * @author Ondrej Sibrava <sibrava@varhall.cz>
 */
class FilesRoute extends AbstractRoute
{
    public function match(\Nette\Http\IRequest $httpRequest)
    {
        $request = parent::match($httpRequest);

        if ($request != NULL) {
            $action = 'default';

            // if ($httpRequest->getMethod() != 'POST' && !$request->getParameter('id'))
            //     throw new \Nette\InvalidArgumentException('Missing or invalid parameter ID');

            $files = NULL;
            $data = [];

            switch ($httpRequest->getMethod()) {
                case 'GET':
                    $action = 'download';
                    break;

                case 'POST':
                    $action = 'upload';

                    /*
                     upload format:
                    {
                        files: { data: 'base64string', name: 'filename' },
                        data: { optionalvalues }
                    }
                     */

                    $content = json_decode(file_get_contents('php://input'), TRUE);

                    $files = !empty($httpRequest->getFiles())
                        ? array_values($httpRequest->getFiles())
                        : FileUtils::retrieveFiles($content, 'files');

                    $data = !empty($request->getPost())
                        ? $request->getPost()
                        : isset($content['data']) ? $content['data'] : [];

                    break;

                case 'HEAD':
                    $action = 'meta';
                    break;

                case 'DELETE':
                    $action = 'delete';
                    break;
            }

            $params = $request->getParameters();

            $params['action'] = strtolower($action);
            $params['data'] = $data;

            if ($files)
                $params['files'] = $files;

            $request->setParameters($params);
        }

        return $request;
    }

    protected function getJsonData(array $input, $dataKey = 'data')
    {
        if (!empty($dataKey) && !isset($input[$dataKey]))
            return [];

        return $input[$dataKey];
    }
}