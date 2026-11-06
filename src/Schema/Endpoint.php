<?php

namespace Varhall\Restino\Schema;

class Endpoint
{
    public string $path;

    public string $method;

    /** @var class-string */
    public string $controller;

    public string $action;

    /** endpoint is available for clients with version >= since (null = no lower bound) */
    public ?int $since;

    /** endpoint is available for clients with version < before (null = no upper bound) */
    public ?int $before;

    /**
     * @param class-string $controller
     */
    public function __construct(string $path, string $method, string $controller, string $action, ?int $since = null, ?int $before = null)
    {
        if ($since !== null && $before !== null && $since >= $before) {
            throw new \Nette\InvalidArgumentException("Invalid version range of endpoint {$controller}::{$action}: since ({$since}) must be lower than before ({$before}).");
        }

        $this->path = $path;
        $this->method = $method;
        $this->controller = $controller;
        $this->action = $action;
        $this->since = $since;
        $this->before = $before;
    }

    /**
     * Endpoint is available for the client version
     */
    public function accepts(int $version): bool
    {
        return ($this->since === null || $version >= $this->since)
            && ($this->before === null || $version < $this->before);
    }

    /**
     * Version ranges of both endpoints have a common version
     */
    public function overlaps(Endpoint $other): bool
    {
        $low = max($this->since ?? PHP_INT_MIN, $other->since ?? PHP_INT_MIN);
        $high = min($this->before ?? PHP_INT_MAX, $other->before ?? PHP_INT_MAX);

        return $low < $high;
    }

    public function isVersioned(): bool
    {
        return $this->since !== null || $this->before !== null;
    }

    public function describeRange(): string
    {
        if (!$this->isVersioned()) {
            return 'all versions';
        }

        return implode(', ', array_filter([
            $this->since !== null ? "since {$this->since}" : null,
            $this->before !== null ? "before {$this->before}" : null,
        ]));
    }

    public function getPattern(): string
    {
        // /api/users/{id}
        return (string) preg_replace('#\{([a-zA-Z0-9_]+)\}#', '(?P<$1>[a-zA-Z0-9_-]+)', $this->path);

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