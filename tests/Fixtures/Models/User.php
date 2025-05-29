<?php

namespace Tests\Fixtures\Models;

use Varhall\Utilino\ISerializable;

class User implements ISerializable
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }


    public function toArray()
    {
        return $this->data;
    }

    public function toJson()
    {
        return json_encode($this->data);
    }
}