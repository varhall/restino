<?php

namespace Varhall\Restino\Results;

interface IExpandable
{
    public function expansions(): array;
}