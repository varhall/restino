<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Nette\Bridges\ApplicationDI\ApplicationExtension;
use Nette\Bridges\ApplicationDI\RoutingExtension;
use Nette\Bridges\HttpDI\HttpExtension;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Varhall\Restino\DI\RestinoExtension;
use Varhall\Restino\Results\CollectionResult;
use Varhall\Restino\Results\SimpleResult;
use Varhall\Utilino\Collections\ArrayCollection;


// keep the data small - tests run with code coverage as well
function collection(int $count = 300): ArrayCollection
{
    return new ArrayCollection(range(1, $count));
}

function containerWith(array $restino): Container
{
    $loader = new ContainerLoader(sys_get_temp_dir() . '/restino-tests/' . getmypid(), true);

    $class = $loader->load(function (Compiler $compiler) use ($restino) {
        $compiler->addExtension('http', new HttpExtension(true));
        $compiler->addExtension('routing', new RoutingExtension());
        $compiler->addExtension('application', new ApplicationExtension(false));
        $compiler->addExtension('restino', new RestinoExtension());
        $compiler->loadConfig(Tester\FileMock::create(\Nette\Neon\Neon::encode([ 'restino' => $restino ], true), 'neon'));
    }, md5(serialize($restino)));

    return new $class();
}


/// result

Toolkit::test(function (): void {
    $result = new CollectionResult(collection());

    Assert::same(CollectionResult::DEFAULT_LIMIT, $result->getLimit());

    $result->paginate(5000, null);
    Assert::same(CollectionResult::MAX_LIMIT, $result->getLimit());

    $result->paginate(20, 40);
    Assert::same(20, $result->getLimit());
    Assert::same(40, $result->getOffset());
}, 'CollectionResult: default limits');


Toolkit::test(function (): void {
    $result = new CollectionResult(collection());
    $result->paginate(5000, null);

    // limits are applied when the result is executed - order of paginate() and setLimits() does not matter
    $result->setLimits(50, null);
    Assert::same(5000, $result->getLimit());
    Assert::null($result->getMaxLimit());

    $result->paginate(null, null);
    Assert::same(50, $result->getLimit());

    $data = $result->execute(new \Nette\Http\Response(), mock(\Nette\Http\IRequest::class));
    Assert::count(50, $data);
}, 'CollectionResult: configured default and unlimited maximum');


Toolkit::test(function (): void {
    $result = (new CollectionResult(collection()))->setLimits(10, 200);
    $result->paginate(250, 0);

    Assert::same(200, $result->getLimit());
    Assert::count(200, $result->execute(new \Nette\Http\Response(), mock(\Nette\Http\IRequest::class)));
}, 'CollectionResult: configured maximum caps the requested limit');


Toolkit::test(function (): void {
    $result = new CollectionResult(collection());

    Assert::exception(fn() => $result->setLimits(0, 100), \Nette\InvalidArgumentException::class);
    Assert::exception(fn() => $result->setLimits(200, 100), \Nette\InvalidArgumentException::class);
}, 'CollectionResult: invalid limits');


Toolkit::test(function (): void {
    $original = (new CollectionResult(collection()))->setLimits(10, null);
    $copy = CollectionResult::fromResult($original);

    Assert::same(10, $copy->getDefaultLimit());
    Assert::null($copy->getMaxLimit());
    Assert::true($copy->hasLimits());

    Assert::false(CollectionResult::fromResult(new CollectionResult(collection()))->hasLimits());
}, 'CollectionResult: fromResult() keeps limits');


/// dispatcher and configuration

Toolkit::test(function (): void {
    $dispatcher = containerWith([])->getService('restino.dispatcher');

    $result = $dispatcher->createResult(collection());
    Assert::type(CollectionResult::class, $result);
    Assert::same(CollectionResult::DEFAULT_LIMIT, $result->getDefaultLimit());
    Assert::same(CollectionResult::MAX_LIMIT, $result->getMaxLimit());

    Assert::type(SimpleResult::class, $dispatcher->createResult([ 1, 2 ]));
}, 'Dispatcher: default collection limits');


Toolkit::test(function (): void {
    $dispatcher = containerWith([ 'collection' => [ 'defaultLimit' => 25, 'maxLimit' => null ] ])->getService('restino.dispatcher');

    // created from a returned collection
    $result = $dispatcher->createResult(collection());
    Assert::same(25, $result->getDefaultLimit());
    Assert::null($result->getMaxLimit());

    // CollectionResult returned by the action
    $returned = $dispatcher->createResult(new CollectionResult(collection()));
    Assert::null($returned->getMaxLimit());
}, 'Dispatcher: configured limits, maxLimit null = unlimited');


Toolkit::test(function (): void {
    $dispatcher = containerWith([])->getService('restino.dispatcher');

    // limits set by the action take precedence over the configuration
    $returned = $dispatcher->createResult((new CollectionResult(collection()))->setLimits(500, 50000));
    Assert::same(500, $returned->getDefaultLimit());
    Assert::same(50000, $returned->getMaxLimit());

    Assert::false((new CollectionResult(collection()))->hasLimits());
}, 'Dispatcher: limits of the returned result are kept');


Toolkit::test(function (): void {
    $dispatcher = containerWith([ 'collection' => [ 'maxLimit' => 5000 ] ])->getService('restino.dispatcher');

    $result = $dispatcher->createResult(collection());
    Assert::same(CollectionResult::DEFAULT_LIMIT, $result->getDefaultLimit());
    Assert::same(5000, $result->getMaxLimit());
}, 'Configuration: only maximum changed');


Toolkit::test(function (): void {
    Assert::exception(
        fn() => containerWith([ 'collection' => [ 'defaultLimit' => 0 ] ]),
        \Nette\DI\InvalidConfigurationException::class,
        '%a%defaultLimit%a%'
    );

    Assert::exception(
        fn() => containerWith([ 'collection' => [ 'defaultLimit' => 500, 'maxLimit' => 100 ] ]),
        \Nette\DI\InvalidConfigurationException::class
    );
}, 'Configuration: invalid limits');
