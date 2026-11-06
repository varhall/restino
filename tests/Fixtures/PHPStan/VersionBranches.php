<?php

namespace Tests\Fixtures\PHPStan;

use Varhall\Restino\Versioning\ClientVersion;

/**
 * Analysed by PHPStan with minimum: 2, current: 3 (see ClientVersionBeforeRuleTest)
 */
class VersionBranches
{
    public const LEGACY = 1;
    public const SPLIT_NAME = 3;

    public function run(ClientVersion $version, int $dynamic, \stdClass $other): void
    {
        if ($version->before(3)) {                      // ok
        }

        if ($version->before(self::SPLIT_NAME)) {       // ok - class constant
        }

        if ($version->before(2)) {                      // line 23: dead branch
        }

        if ($version->before(self::LEGACY)) {           // line 26: dead branch
        }

        if ($version->before(4)) {                      // line 29: future version
        }

        if ($version->before($dynamic)) {               // line 32: not constant
        }

        if ($other->before ?? false) {                  // not ClientVersion - ignored
        }
    }
}
