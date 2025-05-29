<?php

namespace Tests\Fixtures\Models;

use Varhall\Restino\Mapping\Rule;

class BookNullable
{
    #[Rule('string:1..', required: false)]
    public ?string $name;

    #[Rule('int', required: false)]
    public ?int $price;
}