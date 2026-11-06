<?php

namespace Varhall\Restino\Versioning;

use Varhall\Restino\Schema\Endpoint;
use Varhall\Restino\Versioning\Attributes\Downgrade;
use Varhall\Restino\Versioning\Attributes\MinVersion;
use Varhall\Restino\Versioning\Attributes\Unversioned;
use Varhall\Restino\Versioning\Attributes\Upgrade;

/**
 * Reads versioning attributes using reflection (results are cached per instance)
 */
class Metadata
{
    private array $cache = [];

    public function isUnversioned(Endpoint $endpoint): bool
    {
        return $this->remember("unversioned:{$endpoint->controller}::{$endpoint->action}", function () use ($endpoint) {
            return !empty($this->classAttributes($endpoint->controller, Unversioned::class))
                || !empty($this->methodAttributes($endpoint->controller, $endpoint->action, Unversioned::class));
        });
    }

    /**
     * Highest MinVersion declared on the controller class or the action
     */
    public function minVersion(Endpoint $endpoint): ?int
    {
        return $this->remember("min:{$endpoint->controller}::{$endpoint->action}", function () use ($endpoint) {
            $versions = array_map(
                fn(MinVersion $attribute) => $attribute->version,
                array_merge(
                    $this->classAttributes($endpoint->controller, MinVersion::class),
                    $this->methodAttributes($endpoint->controller, $endpoint->action, MinVersion::class)
                )
            );

            return empty($versions) ? null : max($versions);
        });
    }

    /**
     * @return Upgrade[] sorted ascending by version
     */
    public function upgrades(string $class, string $method): array
    {
        return $this->remember("upgrade:{$class}::{$method}", function () use ($class, $method) {
            $upgrades = $this->methodAttributes($class, $method, Upgrade::class);
            usort($upgrades, fn(Upgrade $a, Upgrade $b) => $a->before <=> $b->before);

            return $upgrades;
        });
    }

    /**
     * @return Downgrade[] sorted descending by version
     */
    public function actionDowngrades(string $class, string $method): array
    {
        return $this->remember("downgrade:{$class}::{$method}", function () use ($class, $method) {
            return $this->sortDescending($this->methodAttributes($class, $method, Downgrade::class));
        });
    }

    /**
     * @return Downgrade[] sorted descending by version
     */
    public function classDowngrades(string $class): array
    {
        return $this->remember("downgrade:{$class}", function () use ($class) {
            return $this->sortDescending($this->classAttributes($class, Downgrade::class));
        });
    }

    /**
     * @template T of object
     * @param class-string<T> $attribute
     * @return T[]
     */
    public function classAttributes(string $class, string $attribute): array
    {
        if (!class_exists($class)) {
            return [];
        }

        return array_map(
            fn(\ReflectionAttribute $a) => $a->newInstance(),
            (new \ReflectionClass($class))->getAttributes($attribute)
        );
    }

    /**
     * @template T of object
     * @param class-string<T> $attribute
     * @return T[]
     */
    public function methodAttributes(string $class, string $method, string $attribute): array
    {
        if (!method_exists($class, $method)) {
            return [];
        }

        return array_map(
            fn(\ReflectionAttribute $a) => $a->newInstance(),
            (new \ReflectionMethod($class, $method))->getAttributes($attribute)
        );
    }

    /**
     * @param Downgrade[] $downgrades
     * @return Downgrade[]
     */
    private function sortDescending(array $downgrades): array
    {
        // sorting is stable since PHP 8.0 - declaration order is kept for the same version
        usort($downgrades, fn(Downgrade $a, Downgrade $b) => $b->before <=> $a->before);

        return $downgrades;
    }

    private function remember(string $key, callable $factory): mixed
    {
        if (!array_key_exists($key, $this->cache)) {
            $this->cache[$key] = $factory();
        }

        return $this->cache[$key];
    }
}
