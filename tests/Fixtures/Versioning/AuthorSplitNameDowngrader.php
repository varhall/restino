<?php

namespace Tests\Fixtures\Versioning;

use Varhall\Restino\Versioning\Downgrader;

class AuthorSplitNameDowngrader implements Downgrader
{
    public function __invoke(array $data, mixed $source): array
    {
        $data["name"] = "{$data["firstName"]} {$data["lastName"]}";
        unset($data["firstName"], $data["lastName"]);

        return $data;
    }
}
