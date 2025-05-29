<?php

namespace Varhall\Restino\Schema;

class Endpoint
{
    public string $path;

    public string $method;

    public string $controller;

    public string $action;

    public function __construct(string $path, string $method, string $controller, string $action)
    {
        $this->path = $path;
        $this->method = $method;
        $this->controller = $controller;
        $this->action = $action;
    }

    public function getPattern(): string
    {
        // /api/users/{id}
        return preg_replace('#\{([a-zA-Z0-9_]+)\}#', '(?P<$1>[a-zA-Z0-9_-]+)', $this->path);

        // /api/users/{id?}
//        return preg_replace_callback(
//            '#\{([a-zA-Z0-9_]+)(\?)?\}#',
//            function ($matches) {
//                $name = $matches[1];
//                $optional = isset($matches[2]) && $matches[2] === '?';
//
//                $pattern = '(?P<' . $name . '>[a-zA-Z0-9_-]+)';
//                return $optional ? '(?:/' . $pattern . ')?' : '/' . $pattern;
//            },
//            $this->path
//        );
    }
}