<?php

namespace Varhall\Restino\Presenters\Plugins;

use Nette\NotImplementedException;

class PluginConfiguration
{
    protected $action;

    /** @var array */
    protected $args = [];

    /** @var array */
    protected $only = [];

    /** @var array */
    protected $except = [];

    public function __construct($action)
    {
        $this->action = $action;
    }

    public function canRun(string $method): bool
    {
        if (!empty($this->only) && !in_array($method, $this->only))
            return false;

        if (!empty($this->except) && in_array($method, $this->except))
            return false;

        return true;
    }

    public function createPlugin(): Plugin
    {
        if (is_object($this->action) && $this->action instanceof Plugin)
            return $this->action;

        else if (is_string($this->action) && class_exists($this->action))
            return new $this->action(...$this->args);

        else if (is_callable($this->action))
            return new ClosurePlugin($this->action);

        throw new \InvalidArgumentException('Cannot create plugin');
    }

    public function only(string|array $methods): self
    {
        $this->only = array_unique(array_merge($this->only, (array) $methods));

        return $this;
    }

    public function except(string|array $methods): self
    {
        $this->except = array_unique(array_merge($this->except, (array) $methods));

        return $this;
    }

    public function reset(): self
    {
        $this->only = [];
        $this->except = [];

        return $this;
    }

    public function disable()
    {
        throw new NotImplementedException('not implemented yet');

        $index = array_search($this, $this->plugins);

        if ($index === FALSE)
            throw new InvalidStateException('Plugin is not registered');

        unset($this->plugins[$index]);

        return $this;
    }

    public function moveAfter($after = NULL)
    {
        throw new NotImplementedException('not implemented yet');

        if (!!$after && !self::find($after, $this->plugins))
            throw new InvalidArgumentException("Plugin '{$after}' not found");

        $this->disable();

        $index = !!$after ? self::findIndex($after, $this->plugins) : count($this->plugins);

        array_splice($this->plugins, $index + 1, 0, $this);

        return $this;
    }

    public function moveBefore($before = NULL)
    {
        throw new NotImplementedException('not implemented yet');

        if (!!$before && !self::find($before, $this->plugins))
            throw new InvalidArgumentException("Plugin '{$before}' not found");

        $this->disable();

        $index = !!$before ?  self::findIndex($before, $this->plugins) : 0;

        array_splice($this->plugins, $index, 0, $this);

        return $this;
    }

    public function args(...$args): self
    {
        $this->args = $args;

        return $this;
    }

    public function getArgs(): array
    {
        return $this->args;
    }
}