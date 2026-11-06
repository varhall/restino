<?php

namespace Varhall\Restino\Versioning\Attributes;

use Varhall\Restino\Versioning\Downgrader;

/**
 * Converts output of shape (before) to shape (before - 1).
 * Applied for clients with version lower than $before, in descending order.
 *
 * On a response class (ISerializable) it converts serialized object, on a controller action it converts
 * the whole action output.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Downgrade
{
    /**
     * @param class-string<Downgrader> $with
     */
    public function __construct(
        public readonly int $before,
        public readonly string $with,
    ) {
    }
}
