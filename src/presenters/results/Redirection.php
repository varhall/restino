<?php

namespace Varhall\Restino\Presenters\Results;

use Varhall\Restino\Presenters\RestRequest;

/**
 * Description of Redirection
 *
 * @author sibrava
 */
class Redirection implements IResult
{
    public $destination = null;
    
    public $args        = null;

    public function __construct($response, $args)
    {
        $this->response = $response;
        $this->args = $args;
    }
    
    public function run(RestRequest $request)
    {
        $request->getPresenter()->redirect($this->response, $this->args);
    }
}
