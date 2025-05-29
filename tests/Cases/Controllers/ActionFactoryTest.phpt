<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Contributte\Tester\Toolkit;
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


