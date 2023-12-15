<?php

use Tester\Assert;
use Tester\Expect;
use Varhall\restino\src\utils\configuration\Configuration;
use Varhall\restino\src\utils\validation\rules\Date;
use Varhall\restino\src\utils\validation\rules\Enum;
use Varhall\restino\src\utils\validation\rules\Required;
use Varhall\restino\src\utils\validation\rules\System;
use Varhall\restino\src\utils\validation\rules\Unique;
use Varhall\restino\src\utils\validation\Validator;

require __DIR__ . '/../../bootstrap.php';

function runTest($rules, $section) {
    return Configuration::create($rules, $section, new Validator());
}

$textRule = [
    'name' => [ 'string:1..', 'required:only=create', 'string:2..:only=update' , 'string:3..:only=create,update' ]
];

$nestedRule = [
    'surname'   => [
        'create' => [ 'required', 'string:1..'],
        'update' => [ 'string:2..' ]
    ],
];

$objectRule = [
    'type'   => [ 'required', Unique::create('Test', [ 'only' => 'create' ]), Enum::create([ 'foo', 'bar' ], [ 'only' => 'create,update' ]) ],
];

$singleRule = [
    'birthdate' => 'date'
];


test('All', function() use ($textRule) {
    Assert::equal([
        'name'  => [ Expect::type(System::class), Expect::type(Required::class), Expect::type(System::class), Expect::type(System::class) ]
    ], runTest($textRule, null));
});

test('Text', function() use ($textRule) {
    Assert::equal([
        'name'  => [ Expect::type(System::class), Expect::type(Required::class), Expect::type(System::class) ]
    ], runTest($textRule, 'create'));
});

test('Nested', function() use ($nestedRule) {
    Assert::equal([
        'surname'  => [ Expect::type(Required::class), Expect::type(System::class) ]
    ], runTest($nestedRule, 'create'));
});

test('Object', function() use ($objectRule) {
    Assert::equal([
        'type'  => [ Expect::type(Required::class), Expect::type(Unique::class), Expect::type(Enum::class) ]
    ], runTest($objectRule, 'create'));
});

test('Single', function() use ($singleRule) {
    Assert::equal([
        'birthdate'  => [ Expect::type(Date::class) ]
    ], runTest($singleRule, 'create'));
});
