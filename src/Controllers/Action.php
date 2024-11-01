<?php

namespace Varhall\Restino\Controllers;

use Nette\Http\IResponse;
use Varhall\Restino\Mapping\MappingService;
use Varhall\Restino\Results\IResult;
use Varhall\Restino\Results\Result;
use Varhall\Restino\Results\Termination;
use Varhall\Utilino\Mapping\Target;

class Action
{
    protected \ReflectionMethod $function;
    protected RestController $controller;
    protected MappingService $mapping;

    public function __construct(\ReflectionMethod $function, RestController $controller, MappingService $mapping)
    {
        $this->function = $function;
        $this->controller = $controller;
        $this->mapping = $mapping;
    }

    public function getName(): string
    {
        return $this->getFunction()->getName();
    }

    public function getFunction(): \ReflectionMethod
    {
        return $this->function;
    }

    public function invoke(RestRequest $request): IResult
    {
        $parameters = [];
        $errors = [];
        foreach ($this->function->getParameters() as $parameter) {
            try {
                $parameters[] = $this->mapping->process(new Target($parameter), $request);
            } catch (\Nette\Schema\ValidationException $ex) {
                $errors = array_merge($errors, $ex->getMessageObjects());
            }
        }

        // handle errors
        if (!empty($errors)) {
            $res = array_combine(
                array_map(fn($e) => implode('.', $e->path), $errors),
                array_map(fn($e) => $e->toString(), $errors)
            );

            $res['_action'] = $this->getName();
            $res['_controller'] = $request->getRequest()->getPresenterName();

            return new Termination($res, IResponse::S400_BadRequest);
        }

        // call controller action and create result from it
        return new Result($this->function->invokeArgs($this->controller, $parameters));
    }
}