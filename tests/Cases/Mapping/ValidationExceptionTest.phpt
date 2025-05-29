<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Varhall\Restino\Mapping\ValidationException;


Toolkit::test(function (): void {
    $data = [ 'foo', 'bar', 'baz' ];

    $ex = new ValidationException($data);

    Assert::same($data, $ex->errors);
    Assert::type(\Nette\InvalidArgumentException::class, $ex);
}, 'testExecute');

