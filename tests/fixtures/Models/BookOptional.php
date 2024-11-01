<?php

namespace Tests\Fixtures\Models;


use Varhall\Utilino\Mapping\Attributes\Rule;

class BookOptional
{
    #[Rule('string:1..')]
    public string $name;

    #[Rule('int')]
    public int $price;
}