<?php

use Tester\Assert;
use Varhall\restino\src\utils\configuration\ConfigurationRule;

require __DIR__ . '/../../bootstrap.php';

function runTest($value) {
    return ConfigurationRule::fromString($value);
}

// parsing

test('', function() {
    $result = runTest('string:1..:only=create');

    Assert::equal('string', $result->name);
    Assert::equal('1..', $result->arguments);
    Assert::equal(['only' => 'create'], $result->modifiers);
});

test('', function() {
    $result = runTest('string:only=create');

    Assert::equal('string', $result->name);
    Assert::null($result->arguments);
    Assert::equal([ 'only' => 'create' ], $result->modifiers);
});

test('', function() {
    $result = runTest('string:1..');

    Assert::equal('string', $result->name);
    Assert::equal('1..', $result->arguments);
    Assert::equal([], $result->modifiers);
});

test('', function() {
    $result = runTest('string');

    Assert::equal('string', $result->name);
    Assert::null($result->arguments);
    Assert::equal([], $result->modifiers);
});
