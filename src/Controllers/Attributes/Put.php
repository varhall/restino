<?php

namespace Varhall\Restino\Controllers\Attributes;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Put extends Action
{
    public function __construct(string $path = '/')
    {
        parent::__construct('PUT', $path);
    }
}