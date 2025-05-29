<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Nette\Application\IPresenter;
use Nette\DI\Container;
use Nette\Application\IPresenterFactory;
use Varhall\Restino\Controllers\ControllerFactory;

/// Test classes

class DummyPresenter implements IPresenter
{
    public function run(\Nette\Application\Request $request): \Nette\Application\Response {}
}



/// Test cases

Toolkit::test(function (): void {
    $container = mock(Container::class);
    $factory = mock(IPresenterFactory::class);

    $controllerFactory = new ControllerFactory($container, $factory);

    $name = 'Restino:Some\Custom\Presenter';
    $result = $controllerFactory->getPresenterClass($name);

    Assert::equal('Some\Custom\Presenter', $result);
}, 'getPresenterClass returns matched class');


Toolkit::test(function (): void {
    $container = mock(Container::class);
    $factory = mock(IPresenterFactory::class);
    $factory->shouldReceive('getPresenterClass')->with('App:Home')->andReturn('App\Presenters\HomePresenter');

    $controllerFactory = new ControllerFactory($container, $factory);

    $name = 'App:Home';
    $result = $controllerFactory->getPresenterClass($name);

    Assert::equal('App\Presenters\HomePresenter', $result);
}, 'getPresenterClass falls back to factory');


Toolkit::test(function (): void {
    $dispatcher = mock(IPresenter::class);

    $container = mock(Container::class);
    $container->shouldReceive('getService')->with('restino.dispatcher')->andReturn($dispatcher);

    $factory = mock(IPresenterFactory::class);

    $controllerFactory = new ControllerFactory($container, $factory);

    $result = $controllerFactory->createPresenter('Restino:Some\Presenter');

    Assert::type(IPresenter::class, $result);
    Assert::same($dispatcher, $result);
}, 'createPresenter returns dispatcher for Restino');


Toolkit::test(function (): void {
    $instance = new DummyPresenter();

    $factory = mock(IPresenterFactory::class);
    $factory->shouldReceive('createPresenter')->with('App:Home')->andReturn($instance);

    $container = mock(Container::class);

    $controllerFactory = new ControllerFactory($container, $factory);

    $result = $controllerFactory->createPresenter('App:Home');

    Assert::type(DummyPresenter::class, $result);
    Assert::same($instance, $result);
}, 'createPresenter creates instance for normal presenter');
