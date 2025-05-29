<?php

namespace Varhall\Restino\Schema;

class Schema
{
    /** @var Group[] */
    public array $groups = [];

    public function __construct(array $groups = [])
    {
        $this->groups = $groups;
    }
}