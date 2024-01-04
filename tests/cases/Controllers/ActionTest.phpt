<?php

namespace Tests\Cases\Controllers;

use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Varhall\Restino\Controllers\Action;
use Varhall\Restino\Results\Result;


require_once __DIR__ . '/../../bootstrap.php';

class ActionTest extends BaseTestCase
{
    public function testInvoke_success()
    {
        $method = mock(\ReflectionMethod::class);
        $method->shouldReceive('getParameters')->andReturn([ mock(\ReflectionParameter::class) ]);
        $method->shouldReceive('invokeArgs')->andReturnTrue();

        $mapping = mock(\Varhall\Restino\Mapping\MappingService::class);
        $mapping->shouldReceive('process')->andReturn([]);
        $action = new Action(
            $method,
            mock(\Varhall\Restino\Controllers\RestController::class),
            $mapping
        );

        Assert::type(Result::class, $action->invoke(mock(\Varhall\Restino\Controllers\RestRequest::class)));
    }
}

(new ActionTest())->run();
