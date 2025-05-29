<?php

namespace Varhall\Restino\Filters;

use Nette\Http\IResponse;
use Varhall\Restino\Results\IResult;
use Varhall\Restino\Results\Termination;

class Cors implements IFilter
{
    public array $options = [];

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    public function execute(Context $context, callable $next): IResult
    {
        $this->setHeaders($context->getHttpResponse());

        if ($context->getRequest()->getRequest()->getMethod() === 'OPTIONS') {
            return new Termination('', IResponse::S200_OK);
        }

        return $next($context);
    }

    protected function setHeaders(IResponse $response): void
    {
        $response->setHeader('Access-Control-Allow-Origin', $this->options['allow_origin'] ?? '*');

        $response->setHeader('Access-Control-Allow-Headers', join(', ',
            $this->options['allow_headers'] ?? [ 'Content-type', 'Authorization' ]
        ));

        $response->setHeader('Access-Control-Allow-Methods', join(', ',
            $this->options['allow_methods'] ?? [ 'GET', 'POST', 'PUT', 'DELETE' ]
        ));
    }
}