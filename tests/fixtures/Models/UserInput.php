<?php

namespace Tests\Fixtures\Models;

use Varhall\Restino\Mapping\Rule;

class UserInput
{
    #[Rule('string:3..')]
    public string $name;

    #[Rule('string:1..')]
    public string $surname;

    #[Rule('email')]
    public string $email;

    #[Rule('int', required: false)]
    public $age;

    public \DateTime $created;

    public Address $address;
}
