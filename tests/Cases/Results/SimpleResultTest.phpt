<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Ninjify\Nunjuck\Toolkit;
use Nette\Http\IResponse;
use Nette\Http\IRequest;
use Tester\Assert;
use Varhall\Restino\Results\SimpleResult;
use Varhall\Utilino\ISerializable;
use Tests\Fixtures\Models\User;


Toolkit::test(function (): void {
    $data = 10;

    $result = new SimpleResult($data);
    $r = $result->execute(mock(IResponse::class), mock(IRequest::class));

    Assert::equal($data, $r);
}, 'testExecute_scalar');


Toolkit::test(function (): void {
    $data = [ 'name' => 'pepa', 'surname' => 'novak', 'age' => 50 ];
    $object = mock(ISerializable::class);
    $object->shouldReceive('toArray')->andReturn($data);

    $result = new SimpleResult($object);
    $r = $result->execute(mock(IResponse::class), mock(IRequest::class));

    Assert::equal($data, $r);
}, 'testExecute_model');


Toolkit::test(function (): void {
    $data = [ 'name' => 'pepa', 'surname' => 'novak', 'age' => 50 ];

    $result = new SimpleResult((object) $data);
    $r = $result->execute(mock(IResponse::class), mock(IRequest::class));

    Assert::equal($data, $r);
}, 'testExecute_object');


Toolkit::test(function (): void {
    $source = [ 'name' => 'pepa', 'surname' => 'novak', 'age' => 50 ];
    $map = [ 'property' => 'changed' ];

    $result = new SimpleResult((object) $source);
    $result->addMapper(function($item) use ($source, $map) {
        Assert::equal((object) $source, $item);

        return $map;
    });

    $r = $result->execute(mock(IResponse::class), mock(IRequest::class));

    Assert::equal($map, $r);
}, 'testExecute_mapper');


Toolkit::test(function (): void {
    $data = [
        new User([ 'name' => 'pepa', 'surname' => 'novak', 'age' => 50 ]),
        new User([ 'name' => 'karel', 'surname' => 'kral', 'age' => 40 ]),
        new User([ 'name' => 'viktor', 'surname' => 'lusk', 'age' => 18 ]),
    ];

    $result = new SimpleResult(new \ArrayObject($data));
    $r = $result->execute(mock(IResponse::class), mock(IRequest::class));

    Assert::equal(array_map(fn($x) => $x->toArray(), $data), $r);
}, 'testExecute_traversable');


Toolkit::test(function (): void {
    $data = [
        new User([ 'name' => 'pepa', 'surname' => 'novak', 'age' => 50 ]),
        new User([ 'name' => 'karel', 'surname' => 'kral', 'age' => 40 ]),
        new User([ 'name' => 'viktor', 'surname' => 'lusk', 'age' => 18 ]),
    ];

    $result = new SimpleResult($data);
    $r = $result->execute(mock(IResponse::class), mock(IRequest::class));

    Assert::equal(array_map(fn($x) => $x->toArray(), $data), $r);
}, 'testExecute_array');