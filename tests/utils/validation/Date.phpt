<?php

use Tester\Assert;
use \Varhall\Restino\Utils\Validation\Rules\Date;

require __DIR__ . '/../../bootstrap.php';

function testValid($value) {
    return (new Date('test'))->valid($value);
}

test('DateTime', function() {
    Assert::true(testValid(new DateTime()));
});

test('ISO 8601', function() {
    Assert::true(testValid(date('c')));
});

test('Y-m-d', function() {
    Assert::true(testValid(date('Y-m-d')));
});

test('Y-m-d H:i', function() {
    Assert::true(testValid(date('Y-m-d H:i')));
});

test('number', function() {
    Assert::true(testValid(date(10)));
});

test('string', function() {
    Assert::isMatching('^Value .+ is not correct date$', testValid('xxx'));
});

test('toTransformationRule', function() {
    Assert::type(\Varhall\Restino\Utils\Transformation\Transformators\Date::class, (new Date('test'))->toTransformationRule());
});