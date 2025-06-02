<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Varhall\Restino\Results\Serializer;


Toolkit::test(function (): void {
    $serializer = new Serializer();

    Assert::same('hello', $serializer->serialize('hello'));
    Assert::same(123, $serializer->serialize(123));
    Assert::same(null, $serializer->serialize(null));
}, 'serialize() handles scalar types');


Toolkit::test(function (): void {
    $serializer = new Serializer();

    $data = [
        'a' => 1,
        'b' => ['c' => 2],
    ];

    Assert::same($data, $serializer->serialize($data));
}, 'serialize() handles nested arrays');


Toolkit::test(function (): void {
    $serializer = new Serializer();

    $object = new class {
        public string $name = 'John';
        public int $age = 30;
    };

    Assert::same([
        'name' => 'John',
        'age' => 30,
    ], $serializer->serialize($object));
}, 'serialize() handles generic objects using json_encode');


Toolkit::test(function (): void {
    $serializer = new Serializer();

    $iter = new ArrayIterator([1, 2, 3]);

    Assert::same([1, 2, 3], $serializer->serialize($iter));
}, 'serialize() handles Traversable');


Toolkit::test(function (): void {
    $serializer = new Serializer();

    $serializable = mock(\Varhall\Utilino\ISerializable::class);
    $serializable->shouldReceive('toArray')->once()->andReturn([
        'foo' => 1,
        'bar' => [2, 3]
    ]);

    Assert::same([
        'foo' => 1,
        'bar' => [2, 3],
    ], $serializer->serialize($serializable));
}, 'serialize() handles ISerializable objects recursively');


Toolkit::test(function (): void {
    $serializer = new Serializer();

    $activeRow = mock(\Nette\Database\Table\ActiveRow::class);
    $activeRow->shouldReceive('toArray')->once()->andReturn([
        'id' => 1,
        'name' => 'Test'
    ]);

    Assert::same([
        'id' => 1,
        'name' => 'Test'
    ], $serializer->serialize($activeRow));
}, 'serialize() handles Nette ActiveRow');


Toolkit::test(function (): void {
    $serializer = new Serializer();

    $data = 5;

    $result = $serializer->serialize($data, [
        fn($value) => $value * 2,
        fn($value) => 'x' . $value,
    ]);

    Assert::same('x10', $result);
}, 'serialize() applies mappers in correct order');
