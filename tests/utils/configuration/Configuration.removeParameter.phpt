<?php

use Varhall\Restino\Utils\Configuration\Configuration;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

function runTest($value) {
    return Configuration::removeParameter($value, 'only');
}

Assert::equal('string:1..', runTest('string:1..:only=create'));

Assert::equal('string', runTest('string:only=create'));

Assert::equal('string:1..', runTest('string:1..'));

Assert::equal('string', runTest('string'));