<?php

namespace Varhall\Rest\Presenters\Plugins\Results;

/**
 * Description of Redirection
 *
 * @author sibrava
 */
class Redirection implements IPluginResult
{
    public $destination = NULL;
    
    public $args        = NULL;
    
    public function __construct($response, $args)
    {
        $this->response = $response;
        $this->args = $args;
    }
    
    public function run($presenter)
    {
        $presenter->redirect($this->response, $this->args);
    }
}
