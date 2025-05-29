<?php

namespace Varhall\Restino\Filters;


class Configuration
{
    protected IFilter $filter;

    protected array $only = [];

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

    protected function addCondition(array $methods, bool $negative): static
    {
        foreach ($methods as $method) {
            $this->only = array_filter($this->only, fn($m) => $m !== $method && $m !== "!{$method}");
        }

        $this->only[] =  ($negative ? '!' : '') . $method;

        return $this;
    }
}