<?php

namespace Varhall\Restino\Controllers;

use Varhall\Restino\Mapping\MappingService;
use Varhall\Restino\Mapping\ValidationException;
use Varhall\Utilino\Mapping\Target;

class ActionFactory
{
    protected MappingService $mapping;

    public function __construct(MappingService $mapping)
    {
        $this->mapping = $mapping;
    }

    public function create(\ReflectionMethod $function, RestRequest $request): Action
    {
        $parameters = [];
        $errors = [];

        foreach ($function->getParameters() as $parameter) {
            try {
                $parameters[] = $this->mapping->process(new Target($parameter), $request);
            } catch (\Nette\Schema\ValidationException $ex) {
                foreach ($ex->getMessageObjects() as $message) {
                    array_unshift($message->path, $parameter->getName());
                    $errors[] = $message;
                }
            }
        }

        // handle errors
        if (!empty($errors)) {
            $res = array_combine(
                array_map(fn($e) => implode('.', $e->path), $errors),
                array_map(fn($e) => $e->toString(), $errors)
            );

            throw new ValidationException($res);
        }

        return new Action($function, $parameters);
    }
}