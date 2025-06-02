<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Varhall\Restino\Filters\Closure;
use Varhall\Restino\Results\SimpleResult;
use Varhall\Restino\Filters\Context;


Toolkit::test(function (): void {
    $func = function(Context $request, callable $next) {
        return $next($request);
    };

    $filter = new Closure($func);

    $result = $filter->execute(mock(Context::class), fn($x) => new SimpleResult(1));

    Assert::equal(1, $result->getData());
}, 'Closure executes and passes through');
