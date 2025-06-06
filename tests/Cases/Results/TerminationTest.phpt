<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Ninjify\Nunjuck\Toolkit;
use Nette\Http\IResponse;
use Nette\Http\IRequest;
use Tester\Assert;
use Varhall\Restino\Results\Termination;


Toolkit::test(function (): void {
    $data = [ 'foo', 'bar', 'baz' ];
    $result = new Termination($data, IResponse::S405_MethodNotAllowed);

    $http = mock(IResponse::class);
    $http->shouldReceive('setCode')->with(IResponse::S405_MethodNotAllowed);

    $r = $result->execute($http, mock(IRequest::class));

    Assert::equal($data, $r);
}, 'testExecute');


Toolkit::test(function (): void {
    $data = [ 'foo', 'bar', 'baz' ];
    $result = new Termination($data, IResponse::S405_MethodNotAllowed);

    Assert::equal($data, $result->getData());
}, 'testData');

