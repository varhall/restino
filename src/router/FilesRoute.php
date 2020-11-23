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
    public function match(\Nette\Http\IRequest $httpRequest): ?array
    {
        $request = parent::match($httpRequest);

        if (!!$request) {
            $action = 'default';

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

                    $data = !empty($httpRequest->getPost())
                        ? $httpRequest->getPost()
                        : (isset($content['data']) ? $content['data'] : []);

                    break;

                case 'HEAD':
                    $action = 'meta';
                    break;

                case 'DELETE':
                    $action = 'delete';
                    break;
            }

            $request['action'] = strtolower($action);
            $request['data'] = $data;

            if ($files)
                $request['files'] = $files;
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