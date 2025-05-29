<?php

namespace Varhall\Restino\Schema;

class Group
{
    public string $path;

    /** @var Endpoint[] */
    public array $endpoints = [];

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function add(Endpoint $endpoint): void
    {
        $endpoint->path = rtrim(preg_replace('#/+#i', '/', "{$this->path}/{$endpoint->path}"), '/');

        $this->endpoints[] = $endpoint;
    }
}