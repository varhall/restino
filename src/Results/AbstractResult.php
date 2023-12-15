<?php

namespace Varhall\Restino\Results;

abstract class AbstractResult implements IResult
{
    public array $mappers    = [];

    public function addMapper(callable $mapper): void
    {
        $this->mappers[] = $mapper;
    }

    protected function serialize(mixed $item): mixed
    {
        $result = $this->serializeItem($item);

        foreach ($this->mappers as $mapper) {
            $result = $mapper($result, $item);
        }

        return $result;
    }

    private function serializeItem(mixed $data): mixed
    {
        if (is_scalar($data) || is_null($data)) {
            return $data;

        } else if ($data instanceof \Varhall\Utilino\ISerializable) {
            return $data->toArray();

        } else if ($data instanceof \Nette\Database\Table\ActiveRow) {
            return $data->toArray();

        } else if ($data instanceof \Traversable) {
            return array_map(fn($item) => $this->serialize($item), iterator_to_array($data));

        } else if (is_array($data)) {
            return array_map(fn($item) => $this->serialize($item), $data);

        } else if (is_object($data)) {
            return json_decode(json_encode($data), true);

        }

        return [];
    }
}