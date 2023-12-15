<?php

use Tester\Assert;
use Tester\Expect;
use Varhall\restino\src\utils\transformation\Transformator;

require __DIR__ . '/../../bootstrap.php';

function runTest($data, $rules) {
    return Transformator::instance()->transformate($data, $rules, 'create');
}

function simpleAssert($value, $rule, $expected, $expectedValue = null) {
    $data   = [ 'foo'   => $value ];
    $rules  = [ 'foo'   => $rule ];

    $result = runTest($data, $rules);

    Assert::equal([ 'foo' => Expect::type($expected) ], $result);

    if ($expectedValue !== null)
        Assert::equal($expectedValue, $result['foo']);
}

// parsing

test('Complex', function() {
    $data = [
        'foo'   => '5',
        'bar'   => 'hello',
    ];

    $rules = [
        'foo'   => 'int:5..',
        'bar'   => [ 'string', 'required:only=create' ],
    ];

    Assert::equal([ 'foo' => Expect::type('int'), 'bar' => Expect::type('string') ], runTest($data, $rules));
});

test('Number', function() {
    simpleAssert('5', 'int:5..', 'int', 5);
});

test('Date', function() {
    $date = new DateTime();
    simpleAssert($date->format('c'), 'date', DateTime::class);
});

test('Boolean', function() {
    simpleAssert('1', 'bool', 'bool', true);
    simpleAssert(1, 'bool', 'bool', true);
    simpleAssert('true', 'bool', 'bool', true);

    simpleAssert('0', 'bool', 'bool', false);
    simpleAssert(0, 'bool', 'bool', false);
    simpleAssert('false', 'bool', 'bool', false);
});

test('Enum', function() {
    simpleAssert('hello', \Varhall\restino\src\utils\validation\rules\Enum::create(['foo', 'bar']), 'string', 'hello');
});