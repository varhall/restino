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
