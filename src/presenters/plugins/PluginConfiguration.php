<?php

namespace Varhall\Restino\Presenters\Plugins;

class PluginConfiguration
{
    protected $plugins = NULL;

    protected $action = NULL;

    protected $args = [];

    protected $only = [];

    protected $except = [];

    public function __construct(array &$plugins, $action)
    {
        $this->plugins = &$plugins;
        $this->action = $action;
    }

    public static function find($search, array $plugins)
    {
        foreach ($plugins as $plugin) {
            if ($plugin->action === $search)
                return $plugin;
        }

        return NULL;
    }

    public static function findIndex($search, array $plugins)
    {
        return array_search(static::find($search, $plugins), $plugins);
    }

    public function canRun($method)
    {
        if (!empty($this->only) && !in_array($method, $this->only))
            return FALSE;

        if (!empty($this->except) && in_array($method, $this->except))
            return FALSE;

        return TRUE;
    }

    public function createPlugin()
    {
        if (is_object($this->action) && $this->action instanceof Plugin)
            return $this->action;

        else if (is_string($this->action) && class_exists($this->action))
            return new $this->action();

        else if (is_callable($this->action))
            return new ClosurePlugin($this->action);

        throw new \InvalidArgumentException('Cannot create plugin');
    }

    public function only($methods)
    {
        $this->only = array_unique(array_merge($this->only, (array) $methods));

        return $this;
    }

    public function except($methods)
    {
        $this->except = array_unique(array_merge($this->except, (array) $methods));

        return $this;
    }

    public function reset()
    {
        $this->only = [];
        $this->except = [];

        return $this;
    }

    public function disable()
    {
        $index = array_search($this, $this->plugins);

        if ($index === FALSE)
            throw new InvalidStateException('Plugin is not registered');

        unset($this->plugins[$index]);

        return $this;
    }

    public function moveAfter($after = NULL)
    {
        if (!!$after && !self::find($after, $this->plugins))
            throw new InvalidArgumentException("Plugin '{$after}' not found");

        $this->disable();

        $index = !!$after ? self::findIndex($after, $this->plugins) : count($this->plugins);

        array_splice($this->plugins, $index + 1, 0, $this);

        return $this;
    }

    public function moveBefore($before = NULL)
    {
        if (!!$before && !self::find($before, $this->plugins))
            throw new InvalidArgumentException("Plugin '{$before}' not found");

        $this->disable();

        $index = !!$before ?  self::findIndex($before, $this->plugins) : 0;

        array_splice($this->plugins, $index, 0, $this);

        return $this;
    }

    public function args(...$args)
    {
        $this->args = $args;

        return $this;
    }

    public function getArgs()
    {
        return $this->args;
    }
}