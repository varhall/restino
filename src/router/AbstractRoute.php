<?php

namespace Varhall\Restino\Router;

use Nette\Application\Routers\Route;
use Nette\InvalidArgumentException;
use Varhall\Restino\Utils\FileUtils;

/**
 * Description of RestRoute
 *
 * @author Ondrej Sibrava <sibrava@varhall.cz>
 */
abstract class AbstractRoute extends Route
{
    protected function getBase64Files(array $input, $filesKey = 'files')
    {
        if (!empty($filesKey) && !isset($input[$filesKey]))
                return [];
        
        else if (!empty($filesKey))
            $input = $input[$filesKey];
        
        // prisel surovy base64 string nebo objekt s vlastnosti base64
        if (!is_array($input) || isset($input['base64']))
            $input = [$input];

        $files = [];
        foreach ($input as $key => $file) {
            try {
                $base64 = isset($file['base64']) ? $file['base64'] : $file;
                $name = isset($file['name']) ? $file['name'] : 'unknown_file';

                $files[$key] = FileUtils::fromBase64($base64, $name);

            } catch (InvalidArgumentException $ex) {
                // skip file
            }
        }

        return $files;
    }
    
    /**
     * Obali dosle parametry do $params['data'], pokud v tomto tvaru jiz neprisly
     * 
     * @param type $params
     * @param type $source
     * @param type $keys
     */
    protected function copyInputParams(&$params, $source, $keys)
    {
        $found = FALSE;
        
        foreach ($keys as $key) {
            if (isset($source[$key])) {
                $params[$key] = $source[$key];
                $found = TRUE;
            }
        }
        
        if (!$found)
            $params['data'] = $source;
    }
}
