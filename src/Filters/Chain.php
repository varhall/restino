<?php

namespace Varhall\Restino\Filters;

use Nette\InvalidArgumentException;

class Chain
{
    protected array $filters = [];

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function build(callable $method, string $action): callable
    {
        foreach (array_reverse($this->filters) as $config) {
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

    public function remove(string $name): void
    {
        if (!array_key_exists($name, $this->filters)) {
            throw new InvalidArgumentException("Filter with name {$name} is not registered");
        }

        unset($this->filters[$name]);
    }
}