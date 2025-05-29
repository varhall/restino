<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Contributte\Tester\Toolkit;
use Tester\Assert;
use Varhall\Restino\Filters\Configuration;
use Varhall\Restino\Filters\IFilter;



Toolkit::test(function (): void {
    $config = new Configuration(mock(IFilter::class));

    Assert::true($config->canRun('index'));
    Assert::true($config->canRun('get'));
    Assert::true($config->canRun('create'));
    Assert::true($config->canRun('update'));
}, 'Configuration: all enabled');


Toolkit::test(function (): void {
    $config = new Configuration(mock(IFilter::class));

    $config->only('index');

    Assert::true($config->canRun('index'));
    Assert::false($config->canRun('get'));
    Assert::false($config->canRun('create'));
    Assert::false($config->canRun('update'));
}, 'Configuration: only one');


Toolkit::test(function (): void {
    $config = new Configuration(mock(IFilter::class));

    $config->except('index');

    Assert::false($config->canRun('index'));
    Assert::true($config->canRun('get'));
    Assert::true($config->canRun('create'));
    Assert::true($config->canRun('update'));
}, 'Configuration: except one');


Toolkit::test(function (): void {
    $config = new Configuration(mock(IFilter::class));

    $config->only('index')
        ->only(['get', 'index'])
        ->except('index')
        ->except('create');

    Assert::false($config->canRun('index'));
    Assert::true($config->canRun('get'));
    Assert::false($config->canRun('create'));
    Assert::true($config->canRun('update'));
}, 'Configuration: only + except combined');


Toolkit::test(function (): void {
    $config = new Configuration(mock(IFilter::class));

    $config->except('index')->reset();

    Assert::true($config->canRun('index'));
    Assert::true($config->canRun('get'));
    Assert::true($config->canRun('create'));
    Assert::true($config->canRun('update'));
}, 'Configuration: reset');
