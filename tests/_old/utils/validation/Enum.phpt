<?php

use Tester\Assert;
use Varhall\restino\src\utils\validation\rules\Enum;

require __DIR__ . '/../../bootstrap.php';

function validate($value, $enum) {
    return (new Enum('test', $enum))->valid($value);
}

function isValid($value, $enum) {
    Assert::true(validate($value, $enum));
}

function isInvalid($value, $enum) {
    Assert::isMatching('^Field not match', validate($value, $enum));
}

isValid('xxx', 'xxx,yyy,zzz');
isValid('xxx', 'xxx, yyy, zzz');
isValid('xxx', 'xxx');
isValid('xxx', [ 'xxx', 'yyy', 'zzz' ]);

isInvalid('xxx', 'aaa,bbb,ccc');
isInvalid('xxx', 'aaa, bbb, ccc');
isInvalid('xxx', 'aaa');
isInvalid('xxx', [ 'aaa', 'bbb', 'ccc' ]);

isInvalid(1, '1,2,3');

test('create', function() {
    $instance = Enum::create([ 'aaa', 'bbb', 'ccc' ]);

    Assert::type(Enum::class, $instance);
    Assert::equal('enum', $instance->name);
    Assert::equal([ 'aaa', 'bbb', 'ccc' ], $instance->arguments);
});