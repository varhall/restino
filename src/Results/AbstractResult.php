<?php

namespace Varhall\Restino\Results;

abstract class AbstractResult implements IResult
{
    public array $mappers               = [];

    protected ?Serializer $serializer   = null;

    public function addMapper(callable $mapper): void
    {
        $this->mappers[] = $mapper;
    }

    public function setSerializer(Serializer $serializer): static
    {
        $this->serializer = $serializer;
        return $this;
    }

    public function getSerializer(): Serializer
    {
        return $this->serializer ??= new Serializer();
    }

    public function serialize(mixed $data): mixed
    {
        return $this->getSerializer()->serialize($data, $this->mappers);
    }
}
