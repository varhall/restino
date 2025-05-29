<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Contributte\Tester\Toolkit;
use Nette\InvalidArgumentException;
use Tester\Assert;
use Varhall\Restino\Filters\Chain;
use Varhall\Restino\Filters\Configuration;
use Varhall\Restino\Filters\Closure;
use Varhall\Restino\Filters\Collection;
use Varhall\Restino\Results\Result;
use Varhall\Restino\Filters\Context;



Toolkit::test(function (): void {
    $manager = new Chain();

    $manager->add('test', function () {});

    Assert::throws(function () use ($manager) {
        $manager->add('test', function () {});
    }, InvalidArgumentException::class);
}, 'Chain: duplicit');


Toolkit::test(function (): void {
    $manager = new Chain();

    $fn1 = function () {};
    $fn2 = function () {};

    $manager->add('fn1', $fn1);
    $manager->add('fn2', $fn2);

    Assert::equal([
        'fn1' => new Configuration(new Closure($fn1)),
        'fn2' => new Configuration(new Closure($fn2)),
    ], $manager->getFilters());
}, 'Chain: register function');


Toolkit::test(function (): void {
    $manager = new Chain();

    $m1 = new Collection();
    $manager->add('m1', $m1);

    Assert::equal([
        'm1' => new Configuration($m1),
    ], $manager->getFilters());
}, 'Chain: register instance');


Toolkit::test(function (): void {
    $class = Collection::class;

    $manager = new Chain();

    $manager->add('m1', $class);

    Assert::equal([
        'm1' => new Configuration(new $class()),
    ], $manager->getFilters());
}, 'Chain: register container');


Toolkit::test(function (): void {
    $manager = new Chain();

    $manager->add('foo', function () {});
    $manager->add('bar', function () {});

    Assert::type(Configuration::class, $manager->get('foo'));
}, 'Chain: get correct');


Toolkit::test(function (): void {
    $manager = new Chain();

    $manager->add('foo', function () {});
    $manager->add('bar', function () {});

    Assert::throws(fn() => $manager->get('test'), InvalidArgumentException::class);
}, 'Chain: get missing');


Toolkit::test(function (): void {
    $manager = new Chain();
    $fn = fn($x, $n) => new Result($n($x)->getData() + 1);

    $manager->add('fn1', $fn);
    $manager->add('fn2', $fn);

    $chain = $manager->build(fn($x) => new Result(1), '');
    Assert::equal(3, $chain(mock(Context::class))->getData());
}, 'Chain: full chain');


Toolkit::test(function (): void {
    $manager = new Chain();
    $fn = fn($x) => 1;

    $manager->add('fn1', $fn);
    $manager->add('fn2', $fn);

    Assert::type(Configuration::class, $manager->get('fn1'));
    Assert::type(Configuration::class, $manager->get('fn2'));

    $manager->remove('fn1');

    Assert::type(Configuration::class, $manager->get('fn2'));
    Assert::exception(fn() => $manager->get('fn1'), InvalidArgumentException::class);
    Assert::exception(fn() => $manager->remove('fn1'), InvalidArgumentException::class);
}, 'Chain: remove');
