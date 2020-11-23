<?php

use Tester\Assert;
use Varhall\Restino\Utils\Configuration\ConfigurationRule;

require __DIR__ . '/../../bootstrap.php';

function runTest($modifiers) {
    $rule = new ConfigurationRule('test', null, $modifiers);
    return $rule->allowed('create');
}

Assert::true(runTest([ 'only' => 'create' ]));
Assert::true(runTest([ 'only' => 'create,update' ]));
Assert::true(runTest([ 'only' => ['create', 'update'] ]));
Assert::false(runTest([ 'only' => 'update' ]));
