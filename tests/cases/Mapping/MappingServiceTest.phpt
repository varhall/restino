<?php

namespace Tests\Cases\Casts;

use Nette\Schema\ValidationException;
use Nette\Utils\DateTime;
use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Tests\Fixtures\Models\Address;
use Tests\Fixtures\Models\BookNullable;
use Tests\Fixtures\Models\BookOptional;
use Tests\Fixtures\Models\BookRequired;
use Tests\Fixtures\Models\UserInput;
use Varhall\Restino\Controllers\RestRequest;
use Varhall\Restino\Mapping\MappingService;

require_once __DIR__ . '/../../bootstrap.php';

class MappingServiceTest extends BaseTestCase
{
    public function testScalar_int()
    {
        $service = new MappingService();
        $target = $this->createTarget(function(int $id) {});

        $result = $service->process($target, $this->prepareRequest());
        Assert::equal(1, $result);
    }

    public function testScalar_int_string()
    {
        $service = new MappingService();
        $target = $this->createTarget(function(int $count) {});

        $result = $service->process($target, $this->prepareRequest());
        Assert::equal(10, $result);
    }

    public function testScalar_date()
    {
        $service = new MappingService();
        $target = $this->createTarget(function(DateTime $created) {});

        $result = $service->process($target, $this->prepareRequest());
        Assert::equal(DateTime::from('2023-11-24T10:00:00')->format('Y-m-d H:i'), $result->format('Y-m-d H:i'));
    }

    public function testClass_simple()
    {
        $service = new MappingService();
        $target = $this->createTarget(function(Address $address) {});

        $expected = new Address();
        $expected->city = 'Prague';
        $expected->street = 'Test';

        $result = $service->process($target, $this->prepareRequest([ 'street' => $expected->street, 'city' => $expected->city, 'id' => 1]));

        Assert::equal($expected, $result);
    }

    public function testClass_complex()
    {
        $service = new MappingService();
        $target = $this->createTarget(function(UserInput $data) {});

        $result = $service->process($target, $this->prepareRequest());

        $expected = new UserInput();
        $expected->name = 'pepa';
        $expected->surname = 'novak';
        $expected->email = 'a@a.com';
        $expected->created = DateTime::from('2023-11-24T10:00:00');
        $expected->address = new Address();
        $expected->address->street = 'Test';
        $expected->address->city = 'Prague';

        Assert::equal($expected, $result);
    }

    public function testNullable_int()
    {
        $service = new MappingService();
        $target = $this->createTarget(function(?int $id) {});

        $result = $service->process($target, $this->prepareRequest([ 'nothing' => 'value' ]));
        Assert::equal(null, $result);
    }

    public function testScalar_int_required()
    {
        $service = new MappingService();
        $target = $this->createTarget(function(#[Rule('int')] int $id) {});
        $request =  $this->prepareRequest([ 'nothing' => 'value' ]);

        Assert::exception(fn() => $service->process($target, $request), ValidationException::class);
    }

    public function testScalar_int_missing()
    {
        $service = new MappingService();
        $target = $this->createTarget(function(int $id) {});
        $request =  $this->prepareRequest([ 'nothing' => 'value' ]);

        Assert::exception(fn() => $service->process($target, $request), ValidationException::class);
    }

    public function testClass_simple_norule()
    {
        $service = new MappingService();
        $target = $this->createTarget(function(Address $address) {});
        $request = $this->prepareRequest([ 'empty' => 'value' ]);

        $result =  $service->process($target, $request);
        Assert::equal(new Address(), $result);
    }

    public function testClass_simple_required()
    {
        $service = new MappingService();
        $target = $this->createTarget(function(BookRequired $book) {});
        $request = $this->prepareRequest([ 'empty' => 'value' ]);

        Assert::exception(fn() => $service->process($target, $request), ValidationException::class);
    }

    public function testClass_simple_optional()
    {
        $service = new MappingService();
        $target = $this->createTarget(function(BookOptional $book) {});

        $expected = new BookOptional();

        $result = $service->process($target, $this->prepareRequest([ 'empty' => 'value' ]));

        Assert::equal($expected, $result);
    }

    public function testClass_simple_nullable()
    {
        $service = new MappingService();
        $target = $this->createTarget(function(BookNullable $book) {});

        $expected = new BookNullable();
        $expected->name = null;
        $expected->price = null;

        $result = $service->process($target, $this->prepareRequest([ 'name' => null, 'price' => 'null' ]));

        Assert::equal($expected, $result);
    }

    public function testRestRequest()
    {
        $service = new MappingService();
        $target = $this->createTarget(function(RestRequest $request) {});
        $request = spy(RestRequest::class);

        $result = $service->process($target, $request);

        Assert::equal($request, $result);
    }
}

(new MappingServiceTest())->run();
