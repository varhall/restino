<?php

namespace Varhall\Restino\Router;

use Nette\Http\IRequest;
use Nette\Routing\Route;

class ActionRoute extends Route
{
    protected string $method;
    protected \ReflectionMethod $function;

    public function __construct(string $method, string $mask, \ReflectionMethod $function)
    {
        parent::__construct("<base .*>{$mask}", []);

        $this->method = $method;
        $this->function = $function;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getFunction(): \ReflectionMethod
    {
        return $this->function;
    }

    public function match(IRequest $httpRequest): ?array
    {
        if ($httpRequest->getMethod() !== $this->method) {
            return null;
        }

        $match = parent::match($httpRequest);

        if ($match === null) {
            return null;
        }

        unset($match['base']);
        $match += json_decode($httpRequest->getRawBody() ?? '', true) ?? [];

        return $match;
    }
}