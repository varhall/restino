<?php

use Tester\Assert;
use Tester\Expect;
use \Varhall\Restino\Utils\Validation\Validator;
use \Varhall\Restino\Utils\Validation\Rules\Enum;

require __DIR__ . '/../../bootstrap.php';

function runTest($data, $rules) {
    return Validator::instance()->validate($data, $rules, 'create');
}

function simpleAssert($value, $rule, $expectedType = null) {
    $data  = !is_array($value) ? [ 'foo'   => $value ] : $value;
    $rules = [ 'foo'   => $rule ];

    if ($expectedType !== null)
        Assert::equal([ 'foo' => [ 'type' => $expectedType, 'message' => Expect::type('string') ] ], runTest($data, $rules));
    else
        Assert::equal([], runTest($data, $rules));
}


test('Complex', function() {
    $data = [
        'foo'   => 5,
        'bar'   => 'hello',
        'baz'   => 'xxx'
    ];

    $rules = [
        'foo'   => 'number:5..',
        'bar'   => [ 'string', 'required:only=create' ],
        'baz'   => [ 'enum:aaa,bbb', 'required:only=create' ],
        'qux'   => [ 'string', 'required' ]
    ];

    Assert::equal([
        'baz'   => [ 'type' => 'Enum', 'message' => Expect::type('string') ],
        'qux'   => [ 'type' => 'Required', 'message' => Expect::type('string') ],
    ], runTest($data, $rules));
});

test('Single', function() {
    simpleAssert(2, 'int:5..', 'System');
    simpleAssert(5, 'int:5..');
});

test('Multiple', function() {
    simpleAssert([ 'bar' => 5 ], [ 'number:5..', 'required' ], 'Required');
    simpleAssert(2, [ 'number:5..', 'required' ], 'System');
    simpleAssert(5, [ 'number:5..', 'required' ]);
});

test('Required', function() {
    simpleAssert('test', 'required');
    simpleAssert(false, 'required');

    simpleAssert('', 'required', 'Required');
    simpleAssert(null, 'required', 'Required');
});

test('Date', function() {
    simpleAssert('1605774490', 'date');
    simpleAssert(1605774490, 'date');
    simpleAssert(new DateTime(), 'date');
});

test('Boolean', function() {
    simpleAssert('1', 'bool', 'System');
    simpleAssert(1, 'bool', 'System');

    simpleAssert('true', 'bool', 'System');
    simpleAssert('0', 'bool', 'System');
    simpleAssert(0, 'bool', 'System');
    simpleAssert('false', 'bool', 'System');

    simpleAssert('xxx', 'bool', 'System');

    simpleAssert(true, 'bool');
    simpleAssert(false, 'bool');
    simpleAssert(false, [ 'bool', 'required' ]);
});

test('Enum', function() {
    simpleAssert('hello', Enum::create(['foo', 'bar']), 'Enum');
    simpleAssert('foo', Enum::create(['foo', 'bar']));

    simpleAssert('hello', 'enum:foo,bar', 'Enum');
    simpleAssert('foo', 'enum:foo,bar');
});