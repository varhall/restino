<?php

use Varhall\Restino\Utils\Configuration\Configuration;
use \Varhall\Restino\Utils\Validation\Validator;
use \Varhall\Restino\Utils\Validation\Rules\Unique;
use \Varhall\Restino\Utils\Validation\Rules\System;
use \Varhall\Restino\Utils\Validation\Rules\Required;
use \Varhall\Restino\Utils\Validation\Rules\Enum;
use \Varhall\Restino\Utils\Validation\Rules\Date;
use Tester\Assert;
use Tester\Expect;

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
