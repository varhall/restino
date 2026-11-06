<?php

namespace Varhall\Restino\Versioning\Inspection;

use Varhall\Restino\Controllers\Attributes\Action;
use Varhall\Restino\Versioning\Attributes\Downgrade;
use Varhall\Restino\Versioning\Attributes\MinVersion;
use Varhall\Restino\Versioning\Attributes\Upgrade;
use Varhall\Restino\Versioning\Downgrader;
use Varhall\Restino\Versioning\Metadata;
use Varhall\Restino\Versioning\Settings;
use Varhall\Restino\Versioning\Upgrader;

/**
 * Lists versioning constructs (adapters, minimum versions, version ranges of endpoints) and reports
 * the ones which are dead, redundant or invalid for the configured version window.
 *
 * Usage in a test:
 *     Assert::same([], array_map('strval', $inventory->check($classes)));
 */
class Inventory
{
    protected Metadata $metadata;

    public function __construct(
        protected Settings $settings,
        ?Metadata $metadata = null,
    ) {
        $this->metadata = $metadata ?? new Metadata();
    }

    /**
     * @param array<class-string|object> $classes controllers and response classes
     * @return Violation[]
     */
    public function check(array $classes): array
    {
        $violations = [];

        foreach ($this->list($classes) as $item) {
            foreach ($this->inspect($item) as $message) {
                $violations[] = new Violation($item['class'], $item['method'], get_class($item['attribute']), $message);
            }
        }

        foreach ($this->classNames($classes) as $class) {
            array_push($violations, ...$this->checkDuplicates($class));
            array_push($violations, ...$this->checkActionDowngrades($class));
        }

        return $violations;
    }

    /**
     * All versioning constructs
     *
     * @param array<class-string|object> $classes
     * @return array<int, array{class: string, method: string|null, attribute: object, version: int|null}>
     */
    public function list(array $classes): array
    {
        $items = [];

        foreach ($this->classNames($classes) as $class) {
            foreach ([ Downgrade::class, MinVersion::class ] as $type) {
                foreach ($this->metadata->classAttributes($class, $type) as $attribute) {
                    $items[] = $this->item($class, null, $attribute);
                }
            }

            foreach ((new \ReflectionClass($class))->getMethods() as $method) {
                if ($method->getDeclaringClass()->getName() !== $class) {
                    continue;
                }

                foreach ([ Upgrade::class, Downgrade::class, MinVersion::class ] as $type) {
                    foreach ($this->metadata->methodAttributes($class, $method->getName(), $type) as $attribute) {
                        $items[] = $this->item($class, $method->getName(), $attribute);
                    }
                }

                foreach ($method->getAttributes(Action::class, \ReflectionAttribute::IS_INSTANCEOF) as $reflection) {
                    $attribute = $reflection->newInstance();

                    if ($attribute->since !== null || $attribute->before !== null) {
                        $items[] = $this->item($class, $method->getName(), $attribute);
                    }
                }
            }
        }

        return $items;
    }

    /**
     * @return string[]
     */
    protected function inspect(array $item): array
    {
        $attribute = $item['attribute'];
        $minimum = $this->settings->minimum;
        $current = $this->settings->current;
        $messages = [];

        if ($attribute instanceof Upgrade || $attribute instanceof Downgrade) {
            if ($attribute->before <= $minimum) {
                $messages[] = "adapter before {$attribute->before} is never applied, all clients have version >= {$minimum} (minimum) - remove it";
            }

            if ($attribute->before > $current) {
                $messages[] = "adapter before {$attribute->before} refers to a future version (current is {$current})";
            }

            $interface = $attribute instanceof Upgrade ? Upgrader::class : Downgrader::class;

            if (!class_exists($attribute->with)) {
                $messages[] = "adapter class {$attribute->with} does not exist";

            } else if (!is_subclass_of($attribute->with, $interface)) {
                $messages[] = "adapter class {$attribute->with} must implement {$interface}";
            }

        } else if ($attribute instanceof MinVersion) {
            if ($attribute->version <= $minimum) {
                $messages[] = "minimum version {$attribute->version} is redundant, global minimum is {$minimum} - remove it";
            }

            if ($attribute->version > $current) {
                $messages[] = "minimum version {$attribute->version} refers to a future version (current is {$current}), nobody can call the endpoint";
            }

        } else if ($attribute instanceof Action) {
            if ($attribute->before !== null && $attribute->before <= $minimum) {
                $messages[] = "endpoint for versions before {$attribute->before} is unreachable, all clients have version >= {$minimum} (minimum) - remove it";
            }

            if ($attribute->since !== null && $attribute->since <= $minimum) {
                $messages[] = "restriction since {$attribute->since} is redundant, all clients have version >= {$minimum} (minimum) - remove it";
            }

            if ($attribute->since !== null && $attribute->since > $current) {
                $messages[] = "endpoint since {$attribute->since} refers to a future version (current is {$current})";
            }

            if ($attribute->before !== null && $attribute->before > $current) {
                $messages[] = "endpoint before {$attribute->before} refers to a future version (current is {$current})";
            }
        }

        return $messages;
    }

    /**
     * Two adapters of the same kind for the same version on one target
     *
     * @param class-string $class
     *
     * @return Violation[]
     */
    protected function checkDuplicates(string $class): array
    {
        $violations = [];

        $targets = [ [ null, $this->metadata->classAttributes($class, Downgrade::class) ] ];

        foreach ((new \ReflectionClass($class))->getMethods() as $method) {
            if ($method->getDeclaringClass()->getName() !== $class) {
                continue;
            }

            $targets[] = [ $method->getName(), $this->metadata->methodAttributes($class, $method->getName(), Upgrade::class) ];
            $targets[] = [ $method->getName(), $this->metadata->methodAttributes($class, $method->getName(), Downgrade::class) ];
        }

        foreach ($targets as [ $method, $attributes ]) {
            $versions = array_map(fn($attribute) => $attribute->before, $attributes);

            foreach (array_unique(array_diff_assoc($versions, array_unique($versions))) as $version) {
                $violations[] = new Violation(
                    $class,
                    $method,
                    get_class($attributes[0]),
                    "multiple adapters before {$version} - merge them into one"
                );
            }
        }

        return $violations;
    }

    /**
     * Action Downgrade must not be combined with response classes which have their own Downgrade
     * (action adapter would see objects already converted to client version)
     *
     * @param class-string $class
     *
     * @return Violation[]
     */
    protected function checkActionDowngrades(string $class): array
    {
        $violations = [];

        foreach ((new \ReflectionClass($class))->getMethods() as $method) {
            if ($method->getDeclaringClass()->getName() !== $class
                || empty($this->metadata->methodAttributes($class, $method->getName(), Downgrade::class))) {
                continue;
            }

            foreach ($this->returnClasses($method) as $returned) {
                if (!empty($this->metadata->classDowngrades($returned))) {
                    $violations[] = new Violation(
                        $class,
                        $method->getName(),
                        Downgrade::class,
                        "action downgrade cannot be combined with response class {$returned} which has its own Downgrade - move the adapter to the response class"
                    );
                }
            }
        }

        return $violations;
    }

    /**
     * @return string[]
     */
    private function returnClasses(\ReflectionMethod $method): array
    {
        $type = $method->getReturnType();
        $types = $type instanceof \ReflectionUnionType ? $type->getTypes() : [ $type ];

        return array_values(array_filter(array_map(
            fn($t) => $t instanceof \ReflectionNamedType && !$t->isBuiltin() ? $t->getName() : null,
            $types
        )));
    }

    /**
     * @param array<class-string|object> $classes
     * @return class-string[]
     */
    private function classNames(array $classes): array
    {
        return array_values(array_unique(array_map(
            fn($class) => is_object($class) ? get_class($class) : $class,
            $classes
        )));
    }

    /**
     * @return array{class: string, method: string|null, attribute: object, version: int|null}
     */
    private function item(string $class, ?string $method, object $attribute): array
    {
        $version = match (true) {
            $attribute instanceof Upgrade, $attribute instanceof Downgrade => $attribute->before,
            $attribute instanceof MinVersion => $attribute->version,
            default => null,
        };

        return [ 'class' => $class, 'method' => $method, 'attribute' => $attribute, 'version' => $version ];
    }
}
