<?php

namespace Tests\Fixtures\Models;

use Nette\Utils\DateTime;
use Varhall\Utilino\Mapping\Attributes\Rule;

class UserInput
{
    #[Rule('string:3..')]
    public string $name;

    #[Rule('string:1..')]
    public string $surname;

    #[Rule('email')]
    public string $email;

    #[Rule('int')]
    public $age;

    public DateTime $created;

    public Address $address;
}
