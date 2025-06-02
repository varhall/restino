<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Varhall\Restino\Controllers\Action;
use Varhall\Restino\Results\SimpleResult;

/// Test classes

class TestController implements \Varhall\Restino\Controllers\IController
{
    public function test($foo, $bar, $baz): string
    {
        Assert::equal('foo value', $foo);
        Assert::equal('bar value', $bar);
        Assert::equal('baz value', $baz);

        return 'result data';
    }
}


/// Test cases

Toolkit::test(function (): void {

    $data = [
        'foo' => 'foo value',
        'bar' => 'bar value',
        'baz' => 'baz value'
    ];

    $controller = new TestController();
    $method = new \ReflectionMethod($controller, 'test');
    $action = new Action($method, $data);

    $result = $action($controller);

    Assert::equal('result data', $result);

    Assert::equal('test', $action->getName());
    Assert::same($method, $action->getFunction());
}, 'testExecute');

