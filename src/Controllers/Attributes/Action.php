<?php

namespace Varhall\Restino\Controllers\Attributes;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Action
{
    public string $method;

    public string $path;

    public function __construct(string $method, string $path)
    {
        $this->method = $method;
        $this->path = $path;
    }
}