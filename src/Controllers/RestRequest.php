<?php

namespace Varhall\Restino\Controllers;

use Nette\Application\Request;
use Nette\Http\IRequest as HttpRequest;

class RestRequest
{
    protected Request $request;

    protected array $parameters;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->parameters = $request->getParameter('data') ?? [];
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    public function getParameter(string $name, mixed $default = null): mixed
    {
        return $this->getParameters()[$name] ?? $default;
    }

    public function setParameter(string $name, mixed $value): void
    {
        $this->parameters[$name] = $value;
    }
}