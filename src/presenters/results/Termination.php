<?php

namespace Varhall\Restino\Presenters\Results;

use Nette\Http\Response;
use Varhall\Restino\Presenters\RestRequest;

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
    
    public function run(RestRequest $request)
    {
        if ($this->code >= 300)
            $request->getPresenter()->getHttpResponse()->setCode($this->code);

        $response = is_scalar($this->response) ? [ 'message' => $this->response ] : $this->response;
        return (new Json($response))->run($request);
    }
}
