<?php

namespace Tests\Fixtures;

use Nette\Application\Request;
use Varhall\Restino\Controllers\RestRequest;
use Varhall\Utilino\Mapping\Target;

class Utils
{
    public static function createTarget(callable $func, int|string $param = 0): Target
    {
        $reflection = new \ReflectionParameter($func, $param);
        return new Target($reflection);
    }

    public static function prepareRequest(array|null $data = null): RestRequest
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

        return new RestRequest(new Request('', null, $params));
    }
}