<?php

namespace Tests\Cases\Casts;

use Nette\Schema\Elements\Type;
use Nette\Schema\Expect;
use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Varhall\Restino\Mapping\Mappers\Boolean;
use Varhall\Restino\Mapping\Rule;
use Varhall\Restino\Mapping\Target;

require_once __DIR__ . '/../../../bootstrap.php';

class BooleanTest extends BaseTestCase
{
    public function testTrue()
    {
        $mapper = new Boolean();

        Assert::true($mapper->apply(true));
        Assert::true($mapper->apply(1));
        Assert::true($mapper->apply('true'));
        Assert::true($mapper->apply('yes'));
    }

    public function testFalse()
    {
        $mapper = new Boolean();

        Assert::false($mapper->apply(false));
        Assert::false($mapper->apply(0));
        Assert::false($mapper->apply('false'));
        Assert::false($mapper->apply('no'));
    }

    public function testSchema_Type()
    {
        $mapper = new Boolean();

        $target = $this->createTarget(function(bool $x) {});
        Assert::equal(Expect::bool(), $mapper->schema($target));
    }

    public function testSchema_Rule()
    {
        $mapper = new Boolean();

        $target = $this->createTarget(function(#[Rule('bool', required: true)] bool $x) {});
        Assert::equal(Expect::bool()->required(true), $mapper->schema($target));
    }
}

(new BooleanTest())->run();
