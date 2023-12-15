<?php

namespace Tests\Fixtures\Models;

use Varhall\Restino\Mapping\Rule;

class BookRequired
{
    #[Rule('string:1..')]
    public string $name;

    #[Rule('int')]
    public int $price;
}