<?php

use Tester\Assert;
use Varhall\restino\src\utils\configuration\ConfigurationRule;

require __DIR__ . '/../../bootstrap.php';

function runTest($modifiers) {
    $rule = new ConfigurationRule('test', null, $modifiers);
    return $rule->allowed('create');
}

Assert::true(runTest([ 'only' => 'create' ]));
Assert::true(runTest([ 'only' => 'create,update' ]));
Assert::true(runTest([ 'only' => ['create', 'update'] ]));
Assert::false(runTest([ 'only' => 'update' ]));
