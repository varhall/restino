<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Varhall\Restino\Schema\SchemaGenerator;
use Varhall\Restino\Schema\Schema;
use Varhall\Restino\Schema\Group;
use Varhall\Restino\Controllers\Attributes\Path;
use Varhall\Restino\Controllers\Attributes\Get;
use Varhall\Restino\Controllers\Attributes\Post;
use Varhall\Restino\Controllers\Attributes\Put;
use Varhall\Restino\Controllers\Attributes\Delete;



/// Test classes

#[Path('/users')]
class DummyController implements \Varhall\Restino\Controllers\IController
{
    #[Get]
    public function list() {}

    #[Post('/')]
    public function create() {}

    #[Get('/{id}')]
    public function get(int $id) {}

    #[Put('/{id}')]
    public function update(int $id) {}

    #[Delete('/{id}')]
    public function delete(int $id) {}

    #[Get('/info')]
    public function info() {}

    #[Get('relative')]
    public function relative() {}

    #[Get('/slash/')]
    public function slash() {}

    public function helper() {} // no annotation, should be ignored
}


class NopathController implements \Varhall\Restino\Controllers\IController
{
    #[Get('/')]
    public function list() {}
}

#[Path('/users')]
class DuplicateController implements \Varhall\Restino\Controllers\IController
{
    #[Get('/{id}')]
    public function foo(int $id) {}
}

#[Path('/users')]
class NonDuplicateController implements \Varhall\Restino\Controllers\IController
{
    #[Post('/info')]
    public function foo(int $id) {}

    #[Get('/{id}/nested')]
    public function nested(int $id) {}
}

#[Path('/foo')]
class NonDuplicatePathController implements \Varhall\Restino\Controllers\IController
{
    #[Post('/info')]
    public function foo(int $id) {}

    #[Get('/{id}/nested')]
    public function nested(int $id) {}
}


/// Test cases

Toolkit::test(function (): void {
    $generator = new SchemaGenerator([new DummyController()]);
    $schema = $generator->getSchema();

    Assert::type(Schema::class, $schema);
    Assert::count(1, $schema->groups);

    /** @var Group $group */
    $group = $schema->groups[0];
    Assert::equal('/users', $group->path);


    $endpoints = $group->endpoints;

    $expected = [
        new \Varhall\Restino\Schema\Endpoint('/users', 'GET', DummyController::class, 'list'),
        new \Varhall\Restino\Schema\Endpoint('/users', 'POST', DummyController::class, 'create'),
        new \Varhall\Restino\Schema\Endpoint('/users/{id}', 'GET', DummyController::class, 'get'),
        new \Varhall\Restino\Schema\Endpoint('/users/{id}', 'PUT', DummyController::class, 'update'),
        new \Varhall\Restino\Schema\Endpoint('/users/{id}', 'DELETE', DummyController::class, 'delete'),
        new \Varhall\Restino\Schema\Endpoint('/users/info', 'GET', DummyController::class, 'info'),
        new \Varhall\Restino\Schema\Endpoint('/users/relative', 'GET', DummyController::class, 'relative'),
        new \Varhall\Restino\Schema\Endpoint('/users/slash', 'GET', DummyController::class, 'slash'),
    ];

    Assert::count(count($expected), $group->endpoints);

    foreach ($expected as $i => $endpoint) {
        Assert::equal($endpoint->path, $endpoints[$i]->path);
        Assert::equal($endpoint->method, $endpoints[$i]->method);
        Assert::equal($endpoint->controller, $endpoints[$i]->controller);
        Assert::equal($endpoint->action, $endpoints[$i]->action);
    }

}, 'SchemaGenerator generates schema from controller attributes correctly.');


Toolkit::test(function (): void {
    $generator = new SchemaGenerator([new NopathController()]);
    $schema = $generator->getSchema();

    Assert::type(Schema::class, $schema);
    Assert::count(0, $schema->groups); // No groups should be generated without Path attribute

}, 'SchemaGenerator does not generate schema for controllers without Path attribute.');

Toolkit::test(function (): void {
    $generator = new SchemaGenerator([new DummyController(), new DuplicateController()]);

    Assert::throws(function() use ($generator) {
        $generator->getSchema();
    }, \Nette\InvalidStateException::class);
}, 'SchemaGenerator throws exception on duplicate endpoints.');

Toolkit::test(function (): void {
    $generator = new SchemaGenerator([new DummyController(), new NonDuplicateController(), new NonDuplicatePathController() ]);

    $results = $generator->getSchema();
    Assert::true(count($results->groups) > 0);
}, 'SchemaGenerator throws exception on duplicate endpoints.');