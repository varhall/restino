<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Varhall\Restino\Controllers\Action;
use Varhall\Restino\Controllers\ActionFactory;
use Varhall\Restino\Controllers\RestRequest;
use Varhall\Restino\Mapping\MappingService;
use Varhall\Utilino\Mapping\Target;
use Varhall\Restino\Mapping\ValidationException;

/// Test classes

class DummyClass
{
    public function exampleMethod(string $foo, int $bar)
    {
    }

    public function exampleObjectMethod(DummyObject $dummy, string $baz)
    {

    }
}

class DummyObject
{
    public string $foo;

    #[\Varhall\Utilino\Mapping\Attributes\Required]
    public int $bar;
}



/// Test cases

Toolkit::test(function (): void {
    $mapping = mock(MappingService::class);
    $mapping->shouldReceive('process')->andReturnUsing(function (Target $target, RestRequest $request) {
        return 'value';
    });

    $factory = new ActionFactory($mapping);

    $request = mock(RestRequest::class);
    $method = new ReflectionMethod(DummyClass::class, 'exampleMethod');

    $action = $factory->create($method, $request);

    Assert::type(Action::class, $action);
    Assert::equal(['value', 'value'], $action->getParameters());
}, 'testCreate_success');


Toolkit::test(function (): void {
    $mapping = mock(MappingService::class);
    $mapping->shouldReceive('process')->andThrow(new \Nette\Schema\ValidationException('',
        [new \Nette\Schema\Message('msg', 'code', [])
    ]));

    $factory = new ActionFactory($mapping);
    $request = mock(RestRequest::class);
    $method = new ReflectionMethod(DummyClass::class, 'exampleMethod');


    Assert::throws(function () use ($factory, $method, $request) {
        $factory->create($method, $request);
    }, ValidationException::class);
}, 'testCreate_validationException');


Toolkit::test(function (): void {
    $factory = new ActionFactory(new MappingService());
    $request = mock(RestRequest::class);
    $request->shouldReceive('getParameters')->andReturn(['hello' => 'world', 'bar' => 'xxx']);
    $method = new ReflectionMethod(DummyClass::class, 'exampleMethod');

    try {
        $factory->create($method, $request);
        Assert::fail('Expected ValidationException not thrown');

    } catch (ValidationException $ex) {
        Assert::hasKey('bar', $ex->errors);
        Assert::hasKey('foo', $ex->errors);
    }
}, 'testCreate_validationException');


Toolkit::test(function (): void {
    $factory = new ActionFactory(new MappingService());
    $request = mock(RestRequest::class);
    $request->shouldReceive('getParameters')->andReturn([ 'foo' => 'xxx' ]);
    $method = new ReflectionMethod(DummyClass::class, 'exampleObjectMethod');

    try {
        $factory->create($method, $request);
        Assert::fail('Expected ValidationException not thrown');

    } catch (ValidationException $ex) {
        Assert::hasKey('dummy.bar', $ex->errors);
        Assert::hasKey('baz', $ex->errors);
    }
}, 'testCreate_validationException');


