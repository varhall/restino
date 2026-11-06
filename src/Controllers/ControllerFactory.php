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

    /**
     * For Restino requests ("Restino:<controller class>") returns the controller class
     */
    public function getPresenterClass(string &$name): string
    {
        /** @var class-string<IPresenter> $class (controller class stands for the presenter in Restino requests) */
        $class = $this->getControllerClass($name) ?? $this->factory->getPresenterClass($name);

        return $class;
    }

    public function createPresenter(string $name): IPresenter
    {
        $class = $this->getControllerClass($name);

        if ($class) {
            $dispatcher = $this->container->getService('restino.dispatcher');
            assert($dispatcher instanceof IPresenter);

            return $dispatcher;
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