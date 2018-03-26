<?php

namespace Varhall\Restino\Presenters\Plugins;


use Nette\Application\UI\Presenter;
use Nette\InvalidStateException;

class PluginConfiguration
{
    protected $plugins = NULL;

    protected $class = NULL;

    protected $parameters = [];

    protected $only = [];

    protected $except = [];

    public function __construct(array &$plugins, $class)
    {
        $this->plugins = &$plugins;

        $this->class = $class;
    }

    public static function find($search, array $plugins)
    {
        foreach ($plugins as $plugin) {
            if ($plugin->class === $search)
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
        return new $this->class();
    }

    public function only($methods)
    {
        $this->only = array_unique(array_merge($this->only, (array) $methods));

        return $this;
    }

    public function except($methods)
    {
        $this->only = array_unique(array_merge($this->only, (array) $methods));

        return $this;
    }

    public function reset()
    {
        $this->only = [];
        $this->except = [];
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
    }

    public function moveBefore($before = NULL)
    {
        if (!!$before && !self::find($before, $this->plugins))
            throw new InvalidArgumentException("Plugin '{$before}' not found");

        $this->disable();

        $index = !!$before ?  self::findIndex($before, $this->plugins) : 0;

        array_splice($this->plugins, $index, 0, $this);
    }
}