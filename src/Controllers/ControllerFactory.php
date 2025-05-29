<?php

namespace Varhall\Restino\Controllers;

use Nette\Application\IPresenter;
use Nette\Application\IPresenterFactory;
use Nette\DI\Container;

class ControllerFactory implements IPresenterFactory
{
    private Container $container;
    private IPresenterFactory $factory;

    public function __construct(Container $container, IPresenterFactory $factory)
    {
        $this->container = $container;
        $this->factory = $factory;
    }

    public function getPresenterClass(string &$name): string
    {
        return $this->getControllerClass($name) ?? $this->factory->getPresenterClass($name);
    }

    public function createPresenter(string $name): IPresenter
    {
        $class = $this->getControllerClass($name);

        if ($class) {
            return $this->container->getService('restino.dispatcher');
        }

        return $this->factory->createPresenter($name);
    }

    public function getControllerClass(string $name): ?string
    {
        if (preg_match('/^Restino:(?<class>.+)/', $name, $matches)) {
            return $matches['class'];
        }

        return null;
    }
}