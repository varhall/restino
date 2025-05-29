<?php

namespace Tests\Fixtures\Models;

use Varhall\Utilino\Mapping\Attributes\Required;
use Varhall\Utilino\Mapping\Attributes\Rule;

class BookRequired
{
    #[Rule('string:1..')]
    #[Required]
    public string $name;

    #[Rule('int')]
    #[Required]
    public int $price;
}