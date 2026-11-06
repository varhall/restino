<?php

namespace Varhall\Restino\Versioning\Attributes;

use Varhall\Restino\Versioning\Upgrader;

/**
 * Converts input parameters of shape (before - 1) to shape (before).
 * Applied for clients with version lower than $before, in ascending order.
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Upgrade
{
    /**
     * @param class-string<Upgrader> $with
     */
    public function __construct(
        public readonly int $before,
        public readonly string $with,
    ) {
    }
}
