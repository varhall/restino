<?php

namespace Varhall\Restino\Filters;

use Nette\DI\Container;
use Nette\InvalidStateException;
use Varhall\Restino\Controllers\RestRequest;
use Varhall\Restino\Schema\Endpoint;

class Context
{
    protected Container $container;

    protected RestRequest $request;

    protected ?Endpoint $endpoint;

    public function __construct(Container $container, RestRequest $request, ?Endpoint $endpoint = null)
    {
        $this->container = $container;
        $this->request = $request;
        $this->endpoint = $endpoint;
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function getRequest(): RestRequest
    {
        return $this->request;
    }

    public function getEndpoint(): Endpoint
    {
        $endpoint = $this->endpoint ?? $this->request->getRequest()->getParameter('_endpoint');

        if (!($endpoint instanceof Endpoint)) {
            throw new InvalidStateException('Endpoint is not available in the filter context.');
        }

        return $endpoint;
    }

    public function getHttpRequest(): \Nette\Http\IRequest
    {
        return $this->container->getByType(\Nette\Http\IRequest::class);
    }

    public function getHttpResponse(): \Nette\Http\IResponse
    {
        return $this->container->getByType(\Nette\Http\IResponse::class);
    }
}
