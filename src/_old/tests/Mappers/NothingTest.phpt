<?php

namespace Tests\Cases\Casts;

use Nette\Schema\Expect;
use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Varhall\Restino\Mapping\Mappers\Nothing;
use Varhall\Restino\Mapping\Rule;

require_once __DIR__ . '/../../../bootstrap.php';

class NothingTest extends BaseTestCase
{
    public function testValue()
    {
        $mapper = new Nothing();
        $value = 'test';

        Assert::same($mapper->apply($value), $value);
    }

    public function testSchema_Type()
    {
        $mapper = new Nothing();

        $target = $this->createTarget(function(int $x) {});
        Assert::equal(Expect::int(), $mapper->schema($target));
    }

    public function testSchema_Mixed()
    {
        $mapper = new Nothing();

        $target = $this->createTarget(function($x) {});
        Assert::equal(Expect::mixed(), $mapper->schema($target));
    }

    public function testSchema_Special()
    {
        $mapper = new Nothing();

        $target = $this->createTarget(function(#[Rule('email')] string $x) {});
        Assert::equal(Expect::email()->required(), $mapper->schema($target));
    }
}

(new NothingTest())->run();
