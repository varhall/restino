<?php

namespace Varhall\Restino\Presenters\Results;

use Nette\Http\Response;

/**
 * Description of Termination
 *
 * @author sibrava
 */
class Termination implements IResult
{
    public $response    = NULL;
    
    public $code        = Response::S400_BAD_REQUEST;
    
    public function __construct($response, $code)
    {
        $this->response = $response;
        $this->code = $code;
    }
    
    public function run($presenter)
    {
        if ($this->code < 300)
            $presenter->sendJson($this->response);

        $presenter->sendJsonError($this->response, $this->code);
    }
}
