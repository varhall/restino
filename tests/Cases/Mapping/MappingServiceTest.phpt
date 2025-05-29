<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Contributte\Tester\Toolkit;
use Nette\Schema\ValidationException;
use Nette\Utils\DateTime;
use Tester\Assert;
use Tests\Fixtures\Models\Address;
use Tests\Fixtures\Models\BookNullable;
use Tests\Fixtures\Models\BookOptional;
use Tests\Fixtures\Models\BookRequired;
use Tests\Fixtures\Models\UserInput;
use Tests\Fixtures\Utils;
use Varhall\Restino\Controllers\RestRequest;
use Varhall\Restino\Mapping\MappingService;
use Varhall\Utilino\Mapping\Attributes\Rule;


Toolkit::test(function (): void {
    $service = new MappingService();
    $target = Utils::createTarget(function(int $id) {});

    $result = $service->process($target, Utils::prepareRequest());
    Assert::equal(1, $result);
}, 'testScalar_int');


Toolkit::test(function (): void {
    $service = new MappingService();
    $target = Utils::createTarget(function(int $count) {});

    $result = $service->process($target, Utils::prepareRequest());
    Assert::equal(10, $result);
}, 'testScalar_int_string');


Toolkit::test(function (): void {
    $service = new MappingService();
    $target = Utils::createTarget(function(DateTime $created) {});

    $result = $service->process($target, Utils::prepareRequest());
    Assert::equal(DateTime::from('2023-11-24T10:00:00')->format('Y-m-d H:i'), $result->format('Y-m-d H:i'));
}, 'testScalar_date');


Toolkit::test(function (): void {
    $service = new MappingService();
    $target = Utils::createTarget(function(Address $address) {});

    $expected = new Address();
    $expected->city = 'Prague';
    $expected->street = 'Test';

    $result = $service->process($target, Utils::prepareRequest([ 'street' => $expected->street, 'city' => $expected->city, 'id' => 1]));

    Assert::equal($expected, $result);
}, 'testClass_simple');


Toolkit::test(function (): void {
    $service = new MappingService();
    $target = Utils::createTarget(function(UserInput $data) {});

    $result = $service->process($target, Utils::prepareRequest());

    $expected = new UserInput();
    $expected->name = 'pepa';
    $expected->surname = 'novak';
    $expected->email = 'a@a.com';
    $expected->created = DateTime::from('2023-11-24T10:00:00');
    $expected->address = new Address();
    $expected->address->street = 'Test';
    $expected->address->city = 'Prague';

    Assert::equal($expected, $result);
}, 'testClass_complex');


Toolkit::test(function (): void {
    $service = new MappingService();
    $target = Utils::createTarget(function(?int $id) {});

    $result = $service->process($target, Utils::prepareRequest([ 'nothing' => 'value' ]));
    Assert::equal(null, $result);
}, 'testNullable_int');


Toolkit::test(function (): void {
    $service = new MappingService();
    $target = Utils::createTarget(function(#[Rule('int')] int $id) {});
    $request =  Utils::prepareRequest([ 'nothing' => 'value' ]);

    Assert::exception(fn() => $service->process($target, $request), ValidationException::class);
}, 'testScalar_int_required');


Toolkit::test(function (): void {
    $service = new MappingService();
    $target = Utils::createTarget(function(int $id) {});
    $request =  Utils::prepareRequest([ 'nothing' => 'value' ]);

    Assert::exception(fn() => $service->process($target, $request), ValidationException::class);
}, 'testScalar_int_missing');


Toolkit::test(function (): void {
    $service = new MappingService();
    $target = Utils::createTarget(function(Address $address) {});
    $request = Utils::prepareRequest([ 'empty' => 'value' ]);

    $result =  $service->process($target, $request);
    Assert::equal(new Address(), $result);
}, 'testClass_simple_norule');


Toolkit::test(function (): void {
    $service = new MappingService();
    $target = Utils::createTarget(function(BookRequired $book) {});
    $request = Utils::prepareRequest([ 'empty' => 'value' ]);

    Assert::exception(fn() => $service->process($target, $request), ValidationException::class);
}, 'testClass_simple_required');


Toolkit::test(function (): void {
    $service = new MappingService();
    $target = Utils::createTarget(function(BookOptional $book) {});

    $expected = new BookOptional();

    $result = $service->process($target, Utils::prepareRequest([ 'empty' => 'value' ]));

    Assert::equal($expected, $result);
}, 'testClass_simple_optional');


Toolkit::test(function (): void {
    $service = new MappingService();
    $target = Utils::createTarget(function(BookNullable $book) {});

    $expected = new BookNullable();
    $expected->name = null;
    $expected->price = null;

    $result = $service->process($target, Utils::prepareRequest([ 'name' => null, 'price' => 'null' ]));

    Assert::equal($expected, $result);
}, 'testClass_simple_nullable');


Toolkit::test(function (): void {
    $service = new MappingService();
    $target = Utils::createTarget(function(RestRequest $request) {});
    $request = spy(RestRequest::class);

    $result = $service->process($target, $request);

    Assert::equal($request, $result);
}, 'testRestRequest');


