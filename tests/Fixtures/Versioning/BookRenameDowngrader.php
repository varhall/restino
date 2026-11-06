<?php

namespace Tests\Fixtures\Versioning;

use Varhall\Restino\Versioning\Downgrader;

class BookRenameDowngrader implements Downgrader
{
    /** @var string[] */
    public static array $seen = [];

    public function __invoke(array $data, mixed $source): array
    {
        // runs in step 2 - author is in shape 2
        self::$seen[] = implode(",", array_keys($data["author"]));
        $data = [ "name" => $data["title"] ] + $data;
        unset($data["title"]);

        return $data;
    }
}
