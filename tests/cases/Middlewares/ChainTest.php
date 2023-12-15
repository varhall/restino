<?php

namespace Tests\Cases\Middlewares;

use Nette\DI\Container;
use Nette\InvalidArgumentException;
use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Varhall\Restino\Controllers\RestRequest;
use Varhall\Restino\Middlewares\Chain;
use Varhall\Restino\Middlewares\Configuration;
use Varhall\Restino\Middlewares\Factory;
use Varhall\Restino\Middlewares\Operations\ClosureMiddleware;
use Varhall\Restino\Middlewares\Operations\CollectionMiddleware;
use Varhall\Restino\Results\Result;

require_once __DIR__ . '/../../bootstrap.php';

class ChainTest extends BaseTestCase
{
    public function testDuplicit()
    {
        $manager = new Chain(mock(Factory::class));

        $manager->add('test', function() {});

        Assert::throws(function() use ($manager) {
            $manager->add('test', function() {});
        }, InvalidArgumentException::class);
    }

    public function testRegister_function()
    {
        $manager = new Chain(mock(Factory::class));

        $fn1 = function() {};
        $fn2 = function() {};

        $manager->add('fn1', $fn1);
        $manager->add('fn2', $fn2);

        Assert::equal([
            'fn1' => new Configuration(new ClosureMiddleware($fn1)),
            'fn2' => new Configuration(new ClosureMiddleware($fn2))
        ], $manager->getMiddlewares());
    }

    public function testRegister_instance()
    {
        $manager = new Chain(mock(Factory::class));

        $m1 = new CollectionMiddleware();
        $manager->add('m1', $m1);

        Assert::equal([ 'm1' => new Configuration($m1) ], $manager->getMiddlewares());
    }

    public function testRegister_container()
    {
        $class = CollectionMiddleware::class;

        $container = mock(Factory::class);
        $container->shouldReceive('create')->with(CollectionMiddleware::class)->andReturn(new $class());
        $manager = new Chain($container);

        $manager->add('m1', $class);

        Assert::equal([ 'm1' => new Configuration(new $class()) ], $manager->getMiddlewares());
    }

    public function testGet_correct()
    {
        $manager = new Chain(mock(Factory::class));

        $manager->add('foo', function() {});
        $manager->add('bar', function() {});

        Assert::type(Configuration::class, $manager->get('foo'));
    }

    public function testGet_missing()
    {
        $manager = new Chain(mock(Factory::class));

        $manager->add('foo', function() {});
        $manager->add('bar', function() {});

        Assert::throws(fn() => $manager->get('test'), InvalidArgumentException::class);
    }

    public function testChain()
    {
        $manager = new Chain(mock(Factory::class));
        $fn = fn($x, $n) => new Result($n($x)->getData() + 1);

        $manager->add('fn1', $fn);
        $manager->add('fn2', $fn);

        $chain = $manager->chain(fn($x) => new Result(1), '');
        Assert::equal(3, $chain(mock(RestRequest::class))->getData());
    }

    public function testRemove()
    {
        $manager = new Chain(mock(Factory::class));
        $fn = fn($x) => 1;

        $manager->add('fn1', $fn);
        $manager->add('fn2', $fn);

        Assert::type(Configuration::class, $manager->get('fn1'));
        Assert::type(Configuration::class, $manager->get('fn2'));

        $manager->remove('fn1');

        Assert::type(Configuration::class, $manager->get('fn2'));
        Assert::exception(fn() => $manager->get('fn1'), InvalidArgumentException::class);
        Assert::exception(fn() => $manager->remove('fn1'), InvalidArgumentException::class);
    }
}

(new ChainTest())->run();
