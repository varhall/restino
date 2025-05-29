<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Tests\Fixtures\Utils;


Toolkit::test(function (): void {
    $data = [
        'foo' => 'foo value',
        'bar' => 'bar value',
        'baz' => 'baz value'
    ];

    $request = Utils::prepareRequest($data);

    Assert::same($data, $request->getParameters());
    Assert::equal('foo value', $request->getParameter('foo'));
    Assert::equal('default', $request->getParameter('nonexistent', 'default'));
}, 'testExecute');

