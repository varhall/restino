<?php

namespace Tests\Fixtures\Versioning;

use Varhall\Restino\Versioning\Downgrader;

class AuthorRenameDowngrader implements Downgrader
{
    public function __invoke(array $data, mixed $source): array
    {
        $data["fullName"] = $data["name"];
        unset($data["name"]);

        return $data;
    }
}
