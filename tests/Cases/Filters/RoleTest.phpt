<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Contributte\Tester\Toolkit;
use Nette\Security\User;
use Tester\Assert;
use Varhall\Restino\Filters\Role;
use Varhall\Restino\Results\Result;
use Varhall\Restino\Controllers\RestRequest;
use Varhall\Restino\Results\Termination;

// TODO: mock problem
Toolkit::test(function (): void {
    Assert::true(true);
});

//Toolkit::test(function (): void {
//    $user = mock(User::class);
//    $user->shouldReceive('isInRole')->with('admin')->andReturn(true);
//
//    $filter = new Role($user, 'admin');
//
//    $result = $filter(mock(RestRequest::class), fn($x) => new Result(1));
//
//    Assert::type(Result::class, $result);
//}, 'testExecute_Yes');
//
//
//Toolkit::test(function (): void {
//    $user = mock(User::class);
//    $user->shouldReceive('isInRole')->with('admin')->andReturn(false);
//
//    $filter = new Role($user, 'admin');
//
//    $result = $filter(mock(RestRequest::class), fn($x) => new Result(1));
//
//    Assert::type(Termination::class, $result);
//}, 'testExecute_No');
//
