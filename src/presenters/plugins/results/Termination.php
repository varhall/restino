<?php

namespace Varhall\Rest\Presenters\Plugins\Results;

/**
 * Description of Termination
 *
 * @author sibrava
 */
class Termination implements IPluginResult
{
    public $response    = NULL;
    
    public $code        = \Nette\Http\Response::S400_BAD_REQUEST;
    
    public function __construct($response, $code)
    {
        $this->response = $response;
        $this->code = $code;
    }
    
    public function run($presenter)
    {
        $presenter->sendJsonError($this->response, $this->code);
    }
}
