<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Ninjify\Nunjuck\Toolkit;
use Nette\Security\User;
use Tester\Assert;
use Varhall\Restino\Controllers\RestRequest;
use Varhall\Restino\Filters\Secured;
use Varhall\Restino\Results\Termination;

// TODO: mock problem
Toolkit::test(function (): void {
    Assert::true(true);
});


//Toolkit::test(function (): void {
//    $user = Mockery::mock(User::class)
//    $user->shouldReceive('isLoggedIn')->andReturn(true);
//
//    $filter = new Authentication($user);
//
//    $result = $filter(mock(RestRequest::class), fn($x) => new Result(1));
//
//    Assert::type(Result::class, $result);
//}, 'Authentication: authenticated user');


//Toolkit::test(function (): void {
//    $user = mock(User::class);
//    $user->shouldReceive('isLoggedIn')->andReturn(false);
//
//    $filter = new Authentication($user);
//
//    $result = $filter(mock(RestRequest::class), fn($x) => new Result(1));
//
//    Assert::type(Termination::class, $result);
//}, 'Authentication: anonymous user');
