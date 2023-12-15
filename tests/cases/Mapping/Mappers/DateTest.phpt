<?php

namespace Tests\Cases\Casts;

use Nette\Schema\Expect;
use Nette\Utils\DateTime;
use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Varhall\Restino\Mapping\Mappers\Date;
use Varhall\Restino\Mapping\Rule;
use Varhall\Restino\Mapping\Target;

require_once __DIR__ . '/../../../bootstrap.php';

class DateTest extends BaseTestCase
{
    public function testTimestamp()
    {
        $mapper = new Date();
        $time = time();

        Assert::equal(DateTime::from($time), $mapper->apply($time));
        Assert::equal(DateTime::from($time), $mapper->apply((string) $time));
    }

    public function testDate_ExplicitZone()
    {
        $mapper = new Date();
        $time = '2028-05-15T16:38:52Z';

        Assert::equal(
            DateTime::from($time)->setTimezone(new \DateTimeZone(date_default_timezone_get()))->format('Y-m-d H:i:s'),
            $mapper->apply($time)->format('Y-m-d H:i:s')
        );
    }

    public function testDate_DefaultZone()
    {
        $mapper = new Date();
        $time = '2028-05-15T16:38:52';

        Assert::equal(DateTime::from($time), $mapper->apply($time));
    }

    public function testSchema_Type()
    {
        $mapper = new Date();

        $target = $this->createTarget(function(DateTime $x) {});
        Assert::equal(Expect::type(\DateTime::class), $mapper->schema($target));
    }

    public function testSchema_Rule()
    {
        $mapper = new Date();

        $target = $this->createTarget(function(#[Rule('date', required: true)] DateTime $x) {});
        Assert::equal(Expect::type(\DateTime::class)->required(true), $mapper->schema($target));
    }
}

(new DateTest())->run();
