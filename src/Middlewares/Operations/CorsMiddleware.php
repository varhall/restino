<?php

namespace Varhall\Restino\Middlewares\Operations;

use Nette\Http\IResponse;
use Varhall\Restino\Controllers\RestRequest;
use Varhall\Restino\Results\IResult;
use Varhall\Restino\Results\Termination;

class CorsMiddleware implements IMiddleware
{
    protected IResponse $response;

    public array $options = [];

    public function __construct(IResponse $response, array $options = [])
    {
        $this->response = $response;
        $this->options = $options;
    }

    public function __invoke(RestRequest $request, callable $next): IResult
    {
        if ($request->getRequest()->getMethod() === 'OPTIONS') {
            $this->response->setHeader('Access-Control-Allow-Origin', $this->options['allow_origin'] ?? '*');

            $this->response->setHeader('Access-Control-Allow-Headers', join(', ',
                $this->options['allow_headers'] ?? [ 'Content-type', 'Authorization' ]
            ));

            $this->response->setHeader('Access-Control-Allow-Methods', join(', ',
                $this->options['allow_methods'] ?? [ 'GET', 'POST', 'PUT', 'DELETE' ]
            ));

            return new Termination('', IResponse::S200_OK);
        }

        return $next($request);
    }
}