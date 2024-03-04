<?php

namespace Tests\Cases\Casts;

use Nette\Schema\Expect;
use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Varhall\Restino\Mapping\Mappers\Nil;
use Varhall\Restino\Mapping\Mappers\Number;
use Varhall\Restino\Mapping\Target;

require_once __DIR__ . '/../../../bootstrap.php';

class NilTest extends BaseTestCase
{
    public function testNumber()
    {
        $mapper = new Nil(new Number());

        Assert::equal(10, $mapper->apply(10));
        Assert::equal(10, $mapper->apply('10'));
    }

    public function testNull()
    {
        $mapper = new Nil(new Number());

        Assert::null($mapper->apply('null'));
        Assert::null($mapper->apply('nil'));
    }

    public function testSchema_Type()
    {
        $mapper = new Nil(new Number());

        $target = $this->createTarget(function(?int $x) {});
        Assert::equal(Expect::number()->nullable(), $mapper->schema($target));
    }
}

(new NilTest())->run();
