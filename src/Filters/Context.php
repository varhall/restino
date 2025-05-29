<?php

namespace Varhall\Restino\Filters;

use Nette\DI\Container;
use Varhall\Restino\Controllers\RestRequest;

class Context
{
    protected Container $container;

    protected RestRequest $request;

    public function __construct(Container $container, RestRequest $request)
    {
        $this->container = $container;
        $this->request = $request;
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function getRequest(): RestRequest
    {
        return $this->request;
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