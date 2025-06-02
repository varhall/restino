<?php

namespace Varhall\Restino\Results;

abstract class AbstractResult implements IResult
{
    public array $mappers               = [];

    public function addMapper(callable $mapper): void
    {
        $this->mappers[] = $mapper;
    }

    public function serialize(mixed $data): mixed
    {
        $serializer = new Serializer();
        return $serializer->serialize($data, $this->mappers);
    }
}