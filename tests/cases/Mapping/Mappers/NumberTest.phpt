<?php

namespace Tests\Cases\Casts;

use Nette\Schema\Expect;
use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Varhall\Restino\Mapping\Mappers\Number;
use Varhall\Restino\Mapping\Rule;

require_once __DIR__ . '/../../../bootstrap.php';

class NumberTest extends BaseTestCase
{
    public function testInteger()
    {
        $mapper = new Number();

        Assert::equal(10, $mapper->apply(10));
        Assert::equal(10, $mapper->apply('10'));
    }

    public function testFloat()
    {
        $mapper = new Number();

        Assert::equal(10.5, $mapper->apply(10.5));
        Assert::equal(10.5, $mapper->apply('10.5'));
    }

    public function testDecimalPoint()
    {
        $mapper = new Number();

        Assert::equal(10.5, $mapper->apply('10.5'));
        Assert::equal(10.5, $mapper->apply('10,5'));
    }

    public function testSchema_Type()
    {
        $mapper = new Number();

        $target = $this->createTarget(function(int $x) {});
        Assert::equal(Expect::number(), $mapper->schema($target));
    }

    public function testSchema_Rule()
    {
        $mapper = new Number();

        $target = $this->createTarget(function(#[Rule('int', required: true)] bool $x) {});
        Assert::equal(Expect::number()->required(true), $mapper->schema($target));
    }
}

(new NumberTest())->run();
