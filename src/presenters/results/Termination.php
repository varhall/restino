<?php

namespace Varhall\Restino\Presenters\Results;

use Nette\Application\Responses\JsonResponse;
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
        if ($this->code < 300)
            return (new Json($this->response))->run($request);

        $request->getPresenter()->getHttpResponse()->setCode($this->code);
        return new JsonResponse([ 'message' => $this->response ]);
    }
}
