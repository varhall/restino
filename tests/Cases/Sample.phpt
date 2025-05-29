<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Contributte\Tester\Toolkit;
use Tester\Assert;

Toolkit::test(function(): void {
    Assert::equal('foo', 'foo');
});