<?php

use Varhall\Restino\Utils\Configuration\Configuration;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

function runTest($value) {
    return Configuration::getParameter($value, 'only');
}

Assert::equal('create', runTest('string:1..:only=create'));

Assert::equal('create', runTest('string:only=create'));

Assert::null(runTest('string:1..'));

Assert::null(runTest('string'));