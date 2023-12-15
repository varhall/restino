<?php

namespace Tests\Cases\Middlewares;

use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Varhall\Restino\Middlewares\Configuration;
use Varhall\Restino\Middlewares\Operations\IMiddleware;

require_once __DIR__ . '/../../bootstrap.php';

class ConfigurationTest extends BaseTestCase
{
    public function testOnly_EnabledAll()
    {
        $config = new Configuration(mock(IMiddleware::class));

        Assert::true($config->canRun('index'));
        Assert::true($config->canRun('get'));
        Assert::true($config->canRun('create'));
        Assert::true($config->canRun('update'));
    }

    public function testOnly_Only()
    {
        $config = new Configuration(mock(IMiddleware::class));

        $config->only('index');

        Assert::true($config->canRun('index'));
        Assert::false($config->canRun('get'));
        Assert::false($config->canRun('create'));
        Assert::false($config->canRun('update'));
    }

    public function testExcept()
    {
        $config = new Configuration(mock(IMiddleware::class));

        $config->except('index');

        Assert::false($config->canRun('index'));
        Assert::true($config->canRun('get'));
        Assert::true($config->canRun('create'));
        Assert::true($config->canRun('update'));
    }

    public function testOnlyExcept()
    {
        $config = new Configuration(mock(IMiddleware::class));

        $config->only('index')
            ->only([ 'get', 'index' ])
            ->except('index')
            ->except('create');

        Assert::false($config->canRun('index'));
        Assert::true($config->canRun('get'));
        Assert::false($config->canRun('create'));
        Assert::true($config->canRun('update'));
    }

    public function testReset()
    {
        $config = new Configuration(mock(IMiddleware::class));

        $config->except('index')->reset();

        Assert::true($config->canRun('index'));
        Assert::true($config->canRun('get'));
        Assert::true($config->canRun('create'));
        Assert::true($config->canRun('update'));
    }
}

(new ConfigurationTest())->run();
