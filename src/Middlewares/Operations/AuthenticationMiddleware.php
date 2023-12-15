<?php

namespace Varhall\Restino\Middlewares\Operations;

use Nette\Http\IResponse;
use Nette\Security\User;
use Varhall\Restino\Controllers\RestRequest;
use Varhall\Restino\Results\IResult;
use Varhall\Restino\Results\Termination;

class AuthenticationMiddleware implements IMiddleware
{
    protected User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function __invoke(RestRequest $request, callable $next): IResult
    {
        if (!$this->user->isLoggedIn()) {
            return new Termination([ 'message' => 'User is not authenticated' ], IResponse::S401_Unauthorized);
        }

        return $next($request);
    }
}