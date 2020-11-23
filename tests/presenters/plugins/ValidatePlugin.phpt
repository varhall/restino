<?php

use Tester\Assert;
use \Varhall\Restino\Presenters\Plugins\ValidatePlugin;

require __DIR__ . '/../../bootstrap.php';
require __DIR__ . '/Plugin.php';

function runTest($rules, $data) {
    return runPluginTest(ValidatePlugin::class, $data, $rules, []);
}


test('Success', function() {
    $data = [ 'foo' => 5 ];
    $result = runTest([ 'foo' => 'int:5..' ], $data);
    Assert::equal($data, $result);
});

test('Fail', function() {
    $result = runTest([ 'foo' => 'int:5..' ], [ 'foo' => 'xxx' ]);
    Assert::type(\Varhall\Restino\Presenters\Results\Termination::class, $result);
});
