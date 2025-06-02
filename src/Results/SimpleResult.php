<?php

namespace Varhall\Restino\Results;

use Nette\Database\Table\ActiveRow;
use Nette\Http\IRequest;
use Nette\Http\IResponse;

class SimpleResult extends AbstractResult
{
    protected mixed $data;

    public function __construct(mixed $data)
    {
        $this->data = $data;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function execute(IResponse $response, IRequest $request): mixed
    {
        if ((is_array($this->data) && array_is_list($this->data)) || ($this->data instanceof \Traversable && !($this->data instanceof ActiveRow))) {
            return array_values(
                array_map(
                    fn($x) => $this->serialize($x),
                    is_array($this->data) ? $this->data : iterator_to_array($this->data)
                )
            );
        }

        return $this->serialize($this->data);
    }
}