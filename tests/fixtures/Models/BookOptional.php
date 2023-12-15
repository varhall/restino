<?php

namespace Tests\Fixtures\Models;

use Varhall\Restino\Mapping\Rule;

class BookOptional
{
    #[Rule('string:1..', required: false)]
    public string $name;

    #[Rule('int', required: false)]
    public int $price;
}