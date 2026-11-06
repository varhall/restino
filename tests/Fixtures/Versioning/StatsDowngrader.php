<?php

namespace Tests\Fixtures\Versioning;

use Varhall\Restino\Versioning\Downgrader;

/**
 * Version 3 returns { total, active }, version 2 returned { count }
 */
class StatsDowngrader implements Downgrader
{
    public function __invoke(array $data, mixed $source): array
    {
        return [ "count" => $data["total"] ];
    }
}
