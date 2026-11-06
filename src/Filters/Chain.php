<?php

namespace Varhall\Restino\Filters;

use Nette\InvalidArgumentException;
use Nette\InvalidStateException;

class Chain
{
    /** @var array<string, Configuration> */
    protected array $filters = [];

    /**
     * Filters in registration order
     *
     * @return array<string, Configuration>
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * Filters in execution order (outermost first) with respect to after() / before() constraints
     *
     * @return array<string, Configuration>
     */
    public function getOrderedFilters(): array
    {
        $names = array_keys($this->filters);
        $index = array_flip($names);

        // preferred position: registration order, constrained filter right behind the last / in front
        // of the first referenced filter
        $priority = array_map(fn($i) => (float) $i, $index);

        // edges: $edges[$a][$b] = true means $a runs before (outside) $b
        $edges = array_fill_keys($names, []);
        $incoming = array_fill_keys($names, 0);

        $connect = function (string $outer, string $inner) use (&$edges, &$incoming): void {
            if ($outer === $inner || isset($edges[$outer][$inner])) {
                return;
            }

            $edges[$outer][$inner] = true;
            $incoming[$inner]++;
        };

        foreach ($this->filters as $name => $config) {
            foreach ($config->getAfter() as $reference) {
                foreach ($this->resolveReference($reference) as $target) {
                    $connect($target, $name);
                    $priority[$name] = max($priority[$name], $index[$target] + 0.5);
                }
            }

            foreach ($config->getBefore() as $reference) {
                foreach ($this->resolveReference($reference) as $target) {
                    $connect($name, $target);
                    $priority[$name] = min($priority[$name], $index[$target] - 0.5);
                }
            }
        }

        // topological sort - always pick the ready filter with the best preferred position
        $ordered = [];
        $ready = array_keys(array_filter($incoming, fn($count) => $count === 0));

        while (!empty($ready)) {
            usort($ready, fn($a, $b) => [ $priority[$a], $index[$a] ] <=> [ $priority[$b], $index[$b] ]);
            $name = array_shift($ready);
            $ordered[$name] = $this->filters[$name];

            foreach (array_keys($edges[$name]) as $inner) {
                if (--$incoming[$inner] === 0) {
                    $ready[] = $inner;
                }
            }
        }

        if (count($ordered) !== count($this->filters)) {
            $cycle = implode(', ', array_diff($names, array_keys($ordered)));
            throw new InvalidStateException("Filters order cannot be resolved, cyclic after/before constraints: {$cycle}");
        }

        return $ordered;
    }

    public function build(callable $method, string $action): callable
    {
        foreach (array_reverse($this->getOrderedFilters()) as $config) {
            if ($config->canRun($action)) {
                $filter = $config->getFilter();
                $method = fn(Context $context): mixed => $filter->execute($context, $method);
            }
        }

        return $method;
    }

    public function add(string $name, mixed $filter): Configuration
    {
        if (array_key_exists($name, $this->filters)) {
            throw new InvalidArgumentException("Filter with name {$name} is already registered");
        }

        if (is_callable($filter) && !($filter instanceof IFilter)) {
            $filter = new Closure($filter);
        }

        $this->filters[$name] = new Configuration(
            ($filter instanceof IFilter) ? $filter : new $filter()
        );

        return $this->filters[$name];
    }

    public function get(string $name): Configuration
    {
        if (!array_key_exists($name, $this->filters)) {
            throw new InvalidArgumentException("Filter with name {$name} is not registered");
        }

        return $this->filters[$name];
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->filters);
    }

    public function remove(string $name): void
    {
        if (!array_key_exists($name, $this->filters)) {
            throw new InvalidArgumentException("Filter with name {$name} is not registered");
        }

        unset($this->filters[$name]);
    }

    /**
     * @return string[] names of filters matching the reference (filter name or class / interface name)
     */
    protected function resolveReference(string $reference): array
    {
        if (array_key_exists($reference, $this->filters)) {
            return [ $reference ];
        }

        if (!class_exists($reference) && !interface_exists($reference)) {
            return [];
        }

        return array_keys(array_filter(
            $this->filters,
            fn(Configuration $config) => $config->getFilter() instanceof $reference
        ));
    }
}
