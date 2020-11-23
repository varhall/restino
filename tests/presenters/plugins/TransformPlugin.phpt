<?php

use Tester\Assert;
use \Varhall\Restino\Presenters\Plugins\TransformPlugin;

require __DIR__ . '/../../bootstrap.php';
require __DIR__ . '/Plugin.php';

function runTest($data, $transformation, $validation = []) {
    return runPluginTest(TransformPlugin::class, $data, $validation, $transformation);
}


test('Transform rule', function() {
    $result = runTest([ 'foo' => '5' ], [ 'foo' => 'int' ]);
    Assert::equal([ 'foo' => 5 ], $result);
});

test('Validation rule', function() {
    $result = runTest([ 'foo' => '5' ], [], [ 'foo' => 'int:5..' ]);
    Assert::equal([ 'foo' => 5 ], $result);
});

test('Disujctive combination', function() {
    $result = runTest([ 'foo' => '5', 'bar' => '1' ], [ 'bar' => 'bool' ], [ 'foo' => 'int:5..' ]);
    Assert::equal([ 'foo' => 5, 'bar' => true ], $result);
});

test('Overlapping combination', function() {
    $result = runTest([ 'foo' => 'hello' ], [ 'foo' => 'uppercase' ], [ 'foo' => 'string' ]);
    Assert::equal([ 'foo' => 'HELLO' ], $result);
});

test('Multiple', function() {
    $result = runTest([ 'foo' => 'heLLo' ], [ 'foo' => [ 'lowercase', 'upperfirst' ] ]);
    Assert::equal([ 'foo' => 'Hello' ], $result);
});

test('Enum', function() {
    $result = runTest([ 'foo' => 'hello' ], [], [ 'foo' => 'enum:xxx,yyy' ]);
    Assert::equal([ 'foo' => 'hello' ], $result);
});