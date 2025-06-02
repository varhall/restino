<?php

namespace Varhall\Restino\Results;

class Serializer
{
    public function serialize(mixed $item, array $mappers = []): mixed
    {
        foreach ($mappers as $mapper) {
            $item = $mapper($item);
        }

        return $this->serializeItem($item);
    }

    private function serializeItem(mixed $data): mixed
    {
        if (is_scalar($data) || is_null($data)) {
            return $data;

        } else if ($data instanceof \Varhall\Utilino\ISerializable) {
            return array_map(fn($item) => $this->serialize($item), $data->toArray());

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