<?php

namespace Varhall\Restino\OldMapping;

use Nette\Schema\Processor;
use Varhall\Restino\Controllers\RestRequest;

class MappingService
{
    public function process(Target $parameter, RestRequest $request): mixed
    {
        if ($parameter->getType()->getName() === RestRequest::class) {
            return $request;
        }

        $processor = new Processor();

        $schema = $parameter->getMapper()->schema($parameter);
        $data = $this->normalize($parameter, $request);

        return $processor->process($schema, $data);
    }

    protected function normalize(Target $target, RestRequest $request): mixed
    {
        $data = $request->getParameters();

        if ($target->isClassType()) {
            return $target->getMapper()->apply($data);
        }

        return array_key_exists($target->getName(), $data)
            ? $target->getMapper()->apply($data[$target->getName()])
            : null;
    }
}