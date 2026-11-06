<?php

namespace Varhall\Restino\Versioning\Attributes;

/**
 * Minimum client version for the endpoint. Effective minimum is max(global minimum, this value).
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class MinVersion
{
    public function __construct(
        public readonly int $version
    ) {
    }
}
