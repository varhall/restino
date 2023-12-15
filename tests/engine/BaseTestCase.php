<?php

namespace Tests\Engine;

use Nette\Application\Request;
use Tester\TestCase;
use Varhall\Restino\Controllers\RestRequest;
use Varhall\Restino\Mapping\Target;

abstract class BaseTestCase extends TestCase
{
    protected function createTarget(callable $func, int|string $param = 0): Target
    {
        $reflection = new \ReflectionParameter($func, $param);
        return new Target($reflection);
    }

    protected function prepareRequest(array $data = null): RestRequest
    {
        $params = $data ?? [
            'id'        => 1,
            'count'     => '10',
            'name'      => 'pepa',
            'surname'   => 'novak',
            'email'     => 'a@a.com',
            'created'   => '2023-11-24T09:00:00Z',
            'address'   => [
                'street' => 'Test',
                'city' => 'Prague'
            ]
        ];

        return new RestRequest(new Request('', null, [ 'data' => $params ]));
    }
}