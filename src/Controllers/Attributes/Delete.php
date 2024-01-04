<?php

namespace Varhall\Restino\Controllers\Attributes;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Delete extends Action
{
    public function __construct(string $path)
    {
        parent::__construct('DELETE', $path);
    }
}