<?php

namespace Tests\Cases\Casts;

use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Tests\Fixtures\Models\Address;
use Varhall\Restino\Mapping\Mappers\Boolean;
use Varhall\Restino\Mapping\Mappers\Date;
use Varhall\Restino\Mapping\Mappers\Nil;
use Varhall\Restino\Mapping\Mappers\Nothing;
use Varhall\Restino\Mapping\Mappers\Number;
use Varhall\Restino\Mapping\Mappers\Structure;
use Varhall\Restino\Mapping\Rule;

require_once __DIR__ . '/../../bootstrap.php';

class TargetTest extends BaseTestCase
{
    public function testMapper_Untyped()
    {
        $target = $this->createTarget(function($id) {});

        Assert::type(Nil::class, $target->getMapper());
    }

    public function testMapper_Type_Boolean()
    {
        $target = $this->createTarget(function(bool $id) {});

        Assert::type(Boolean::class, $target->getMapper());
    }

    public function testMapper_Type_Number()
    {
        $target = $this->createTarget(function(int $id) {});

        Assert::type(Number::class, $target->getMapper());
    }

    public function testMapper_Type_String()
    {
        $target = $this->createTarget(function(string $id) {});

        Assert::type(Nothing::class, $target->getMapper());
    }

    public function testMapper_Type_Date()
    {
        $target = $this->createTarget(function(\DateTime $id) {});

        Assert::type(Date::class, $target->getMapper());
    }

    public function testMapper_Type_Class()
    {
        $target = $this->createTarget(function(Address $id) {});

        Assert::type(Structure::class, $target->getMapper());
    }

    public function testMapper_Type_Nullable()
    {
        $target = $this->createTarget(function(?string $id) {});

        Assert::type(Nil::class, $target->getMapper());
    }

    public function testMapper_Rule_Number()
    {
        $target = $this->createTarget(function(#[Rule('int:1..')] string $id) {}); // string is by test design, prefer attribute

        Assert::type(Number::class, $target->getMapper());
    }

    public function testMapper_Rule_String()
    {
        $target = $this->createTarget(function(#[Rule('string:1..')] bool $id) {}); // bool is by test design, prefer attribute

        Assert::type(Nothing::class, $target->getMapper());
    }

    public function testMapper_Rule_Date()
    {
        $target = $this->createTarget(function(#[Rule('date')] string $id) {}); // string is by test design, prefer attribute

        Assert::type(Date::class, $target->getMapper());
    }

    public function testMapper_Rule_Nullable()
    {
        $target = $this->createTarget(function(#[Rule('date')] ?\DateTime $id) {});

        Assert::type(Nil::class, $target->getMapper());
    }
}

(new TargetTest())->run();
