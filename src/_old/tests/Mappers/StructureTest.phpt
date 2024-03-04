<?php

namespace Tests\Cases\Casts;

use Nette\Schema\Expect;
use Nette\Utils\DateTime;
use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Tests\Fixtures\Models\Address;
use Tests\Fixtures\Models\UserInput;
use Varhall\Restino\Mapping\Mappers\Structure;

require_once __DIR__ . '/../../../bootstrap.php';

class StructureTest extends BaseTestCase
{
    public function testSimple()
    {
        $mapper = new Structure(Address::class);

        $result = $mapper->apply([
            'test'      => 'value',
            'street'    => 'Black',
            'city'      => 'Prague'
        ]);

        Assert::equal([ 'street' => 'Black', 'city' => 'Prague' ], $result);
    }

    public function testComplex()
    {
        $mapper = new Structure(UserInput::class);

        $result = $mapper->apply([
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
        ]);

        $expected = [
            'name'      => 'pepa',
            'surname'   => 'novak',
            'email'     => 'a@a.com',
            'created'   => DateTime::from('2023-11-24T09:00:00Z')->setTimezone(new \DateTimeZone(date_default_timezone_get())),
            'address'   => [
                'street' => 'Test',
                'city' => 'Prague'
            ]
        ];

        Assert::equal($expected, $result);
    }

    public function testSchema_Simple()
    {
        $mapper = new Structure(Address::class);

        $target = $this->createTarget(function(Address $x) {});
        Assert::equal(Expect::from(new Address(), [
            'street'   => Expect::string()->required(false),
            'city'     => Expect::string()->required(false),
        ])->skipDefaults(true), $mapper->schema($target));
    }

    public function testSchema_Complex()
    {
        $mapper = new Structure(UserInput::class);

        $target = $this->createTarget(function(UserInput $x) {});
        Assert::equal(Expect::from(new UserInput(), [
            'name'      => Expect::type('string:3..')->required(),
            'surname'   => Expect::type('string:1..')->required(),
            'email'     => Expect::email()->required(),
            'age'       => Expect::number()->required(false)->nullable(),
            'created'   => Expect::type(\DateTime::class)->required(false),
            'address'   => Expect::from(new Address(), [
                'street'   => Expect::string()->required(false),
                'city'     => Expect::string()->required(false),
            ])->skipDefaults(true)

        ])->skipDefaults(true), $mapper->schema($target));
    }
}

(new StructureTest())->run();
