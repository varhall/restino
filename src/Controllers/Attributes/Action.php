<?php

namespace Varhall\Restino\Controllers\Attributes;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Action
{
    public string $method;

    public string $path;

    /** endpoint is available for clients with version >= since (null = no lower bound) */
    public ?int $since;

    /** endpoint is available for clients with version < before (null = no upper bound) */
    public ?int $before;

    public function __construct(string $method, string $path, ?int $since = null, ?int $before = null)
    {
        $this->method = $method;
        $this->path = $path;
        $this->since = $since;
        $this->before = $before;
    }
}
