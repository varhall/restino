<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Ninjify\Nunjuck\Toolkit;
use Nette\Http\IResponse;
use Tester\Assert;
use Tests\Fixtures\Models\User;
use Varhall\Restino\Results\Result;
use Varhall\Utilino\ISerializable;

Toolkit::test(function (): void {
    $data = 10;

    $result = new Result($data);
    $r = $result->execute(mock(IResponse::class));

    Assert::equal($data, $r);
}, 'testExecute_scalar');


Toolkit::test(function (): void {
    $data = [ 'name' => 'pepa', 'surname' => 'novak', 'age' => 50 ];
    $object = mock(ISerializable::class);
    $object->shouldReceive('toArray')->andReturn($data);

    $result = new Result($object);
    $r = $result->execute(mock(IResponse::class));

    Assert::equal($data, $r);
}, 'testExecute_model');


Toolkit::test(function (): void {
    $data = [
        new User([ 'name' => 'pepa', 'surname' => 'novak', 'age' => 50 ]),
        new User([ 'name' => 'karel', 'surname' => 'kral', 'age' => 40 ]),
        new User([ 'name' => 'viktor', 'surname' => 'lusk', 'age' => 18 ]),
    ];

    $result = new Result(new \ArrayObject($data));
    $r = $result->execute(mock(IResponse::class));

    Assert::equal(array_map(fn($x) => $x->toArray(), $data), $r);
}, 'testExecute_traversable');


Toolkit::test(function (): void {
    $data = [
        new User([ 'name' => 'pepa', 'surname' => 'novak', 'age' => 50 ]),
        new User([ 'name' => 'karel', 'surname' => 'kral', 'age' => 40 ]),
        new User([ 'name' => 'viktor', 'surname' => 'lusk', 'age' => 18 ]),
    ];

    $result = new Result($data);
    $r = $result->execute(mock(IResponse::class));

    Assert::equal(array_map(fn($x) => $x->toArray(), $data), $r);
}, 'testExecute_array');


Toolkit::test(function (): void {
    $data = [ 'name' => 'pepa', 'surname' => 'novak', 'age' => 50 ];

    $result = new Result((object) $data);
    $r = $result->execute(mock(IResponse::class));

    Assert::equal($data, $r);
}, 'testExecute_object');


Toolkit::test(function (): void {
    $source = [ 'name' => 'pepa', 'surname' => 'novak', 'age' => 50 ];
    $map = [ 'property' => 'changed' ];

    $result = new Result((object) $source);
    $result->addMapper(function($result, $item) use ($source, $map) {
        Assert::equal($source, $result);
        Assert::equal((object) $source, $item);

        return $map;
    });

    $r = $result->execute(mock(IResponse::class));

    Assert::equal($map, $r);
}, 'testExecute_mapper');

