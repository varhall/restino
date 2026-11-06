<?php

namespace Varhall\Restino\Results;

use Varhall\Restino\Versioning\Compatibility;
use Varhall\Restino\Versioning\Node;

class Serializer
{
    protected ?Compatibility $compatibility;

    public function __construct(?Compatibility $compatibility = null)
    {
        $this->compatibility = $compatibility;
    }

    public function getCompatibility(): ?Compatibility
    {
        return $this->compatibility;
    }

    public function serialize(mixed $item, array $mappers = []): mixed
    {
        foreach ($mappers as $mapper) {
            $item = $mapper($item);
        }

        if ($this->compatibility?->isActive()) {
            return $this->compatibility->downgrade($this->buildTree($item));
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

    /**
     * Same as serializeItem, but ISerializable objects are kept as nodes for the step-by-step downgrade
     */
    private function buildTree(mixed $data): mixed
    {
        if (is_scalar($data) || is_null($data)) {
            return $data;

        } else if ($data instanceof \Varhall\Utilino\ISerializable) {
            return new Node(get_class($data), $data, array_map(fn($item) => $this->buildTree($item), $data->toArray()));

        } else if ($data instanceof \Nette\Database\Table\ActiveRow) {
            return $data->toArray();

        } else if ($data instanceof \Traversable) {
            return array_map(fn($item) => $this->buildTree($item), iterator_to_array($data));

        } else if (is_array($data)) {
            return array_map(fn($item) => $this->buildTree($item), $data);

        } else if (is_object($data)) {
            return json_decode(json_encode($data), true);

        }

        return [];
    }
}
