<?php

namespace Tests\Fixtures\Versioning;

use Varhall\Restino\Versioning\Downgrader;

class BookInitialsDowngrader implements Downgrader
{
    /** @var string[] */
    public static array $seen = [];

    public function __invoke(array $data, mixed $source): array
    {
        // runs in step 3 - author is still in shape 3
        self::$seen[] = implode(",", array_keys($data["author"]));
        $data["initials"] = $data["author"]["firstName"][0] . $data["author"]["lastName"][0];

        return $data;
    }
}
