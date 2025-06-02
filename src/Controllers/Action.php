<?php

namespace Varhall\Restino\Controllers;

use Varhall\Restino\Results\IResult;
use Varhall\Restino\Results\Result;

class Action
{
    protected \ReflectionMethod $function;
    protected array $parameters;

    public function __construct(\ReflectionMethod $function, array $parameters)
    {
        $this->function = $function;
        $this->parameters = $parameters;
    }

    public function getName(): string
    {
        return $this->getFunction()->getName();
    }

    public function getFunction(): \ReflectionMethod
    {
        return $this->function;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function __invoke(IController $context): mixed
    {
        return $this->function->invokeArgs($context, $this->parameters);
    }
}