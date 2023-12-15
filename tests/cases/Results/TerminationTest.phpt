<?php

namespace Tests\Cases\Results;

use Nette\Http\IResponse;
use Nette\Http\Response;
use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Varhall\Restino\Results\Termination;

require_once __DIR__ . '/../../bootstrap.php';

class TerminationTest extends BaseTestCase
{
    public function testExecute()
    {
        $data = [ 'foo', 'bar', 'baz' ];
        $result = new Termination($data, Response::S405_MethodNotAllowed);

        $http = mock(IResponse::class);
        $http->shouldReceive('setCode')->with(Response::S405_MethodNotAllowed);

        $r = $result->execute($http);

        Assert::equal($data, $r);
    }

    public function testData()
    {
        $data = [ 'foo', 'bar', 'baz' ];
        $result = new Termination($data, Response::S405_MethodNotAllowed);

        Assert::equal($data, $result->getData());
    }

}

(new TerminationTest())->run();
