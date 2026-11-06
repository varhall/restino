<?php

namespace Varhall\Restino\Controllers\Attributes;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Get extends Action
{
    /**
     * @param int|null $since endpoint is available for clients with version >= since
     * @param int|null $before endpoint is available for clients with version < before
     */
    public function __construct(string $path = '/', ?int $since = null, ?int $before = null)
    {
        parent::__construct('GET', $path, $since, $before);
    }
}
