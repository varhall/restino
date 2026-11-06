<?php

namespace Varhall\Restino\Filters;


class Configuration
{
    protected IFilter $filter;

    protected array $only = [];

    /** @var string[] filter names or class names which must run before (outside) this filter */
    protected array $after = [];

    /** @var string[] filter names or class names which must run after (inside) this filter */
    protected array $before = [];

    public function __construct(IFilter $filter)
    {
        $this->filter = $filter;
    }

    public function getFilter(): IFilter
    {
        return $this->filter;
    }

    public function canRun(string $method): bool
    {
        if (empty($this->only) || in_array($method, $this->only)) {
            return true;
        }

        if (in_array("!{$method}", $this->only)) {
            return false;
        }

        return !empty(array_filter($this->only, fn($x) => $x[0] === '!'));
    }

    public function only(string|array $methods): static
    {
        return $this->addCondition((array) $methods, false);
    }

    public function except(string|array $methods): static
    {
        return $this->addCondition((array) $methods, true);
    }

    public function reset(): static
    {
        $this->only = [];
        return $this;
    }

    /**
     * Filter runs after (inside) the referenced filters. Reference is a filter name or a class / interface name.
     * Missing references are ignored. The order is resolved when the chain is built.
     */
    public function after(string|array ...$references): static
    {
        $this->after = array_values(array_unique(array_merge($this->after, $this->flatten($references))));
        return $this;
    }

    /**
     * Filter runs before (outside) the referenced filters. Reference is a filter name or a class / interface name.
     * Missing references are ignored. The order is resolved when the chain is built.
     */
    public function before(string|array ...$references): static
    {
        $this->before = array_values(array_unique(array_merge($this->before, $this->flatten($references))));
        return $this;
    }

    /** @return string[] */
    public function getAfter(): array
    {
        return $this->after;
    }

    /** @return string[] */
    public function getBefore(): array
    {
        return $this->before;
    }

    protected function addCondition(array $methods, bool $negative): static
    {
        foreach ($methods as $method) {
            $this->only = array_filter($this->only, fn($m) => $m !== $method && $m !== "!{$method}");
            $this->only[] = ($negative ? '!' : '') . $method;
        }

        $this->only = array_values($this->only);

        return $this;
    }

    private function flatten(array $references): array
    {
        $result = [];

        array_walk_recursive($references, function ($reference) use (&$result) {
            $result[] = (string) $reference;
        });

        return $result;
    }
}
