<?php

namespace Varhall\Restino\Controllers\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Path {
    public string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }
}