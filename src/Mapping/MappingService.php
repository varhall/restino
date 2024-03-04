<?php

namespace Varhall\Restino\Mapping;

use Nette\Schema\Processor;
use Varhall\Restino\Controllers\RestRequest;
use Varhall\Utilino\Mapping\Target;

class MappingService
{
    public function process(Target $parameter, RestRequest $request): mixed
    {
        if ($parameter->getType()->getName() === RestRequest::class) {
            return $request;
        }

        $data = $request->getParameters();
        $value = $this->isClassType($parameter)
                    ? $data
                    : (array_key_exists($parameter->getName(), $data) ? $data[$parameter->getName()] : null);

        return (new Processor())->process($parameter->schema(), $value);
    }

    protected function isClassType(Target $parameter): bool
    {
        if (!$parameter->getType()) {
            return false;
        }

        $type = $parameter->getType()->getName();

        if (!class_exists($type)) {
            return false;
        }

        if ($type === \DateTime::class || is_subclass_of($type, \DateTime::class)) {
            return false;
        }

        return true;
    }
}