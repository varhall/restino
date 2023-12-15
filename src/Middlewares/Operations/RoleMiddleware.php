<?php

namespace Varhall\Restino\Middlewares\Operations;

use Nette\Http\IResponse;
use Nette\Security\User;
use Varhall\Restino\Controllers\RestRequest;
use Varhall\Restino\Results\IResult;
use Varhall\Restino\Results\Termination;

class RoleMiddleware implements IMiddleware
{
    protected User $user;
    protected string $role;

    public function __construct(User $user, string $role)
    {
        $this->user = $user;
        $this->role = $role;
    }

    public function __invoke(RestRequest $request, callable $next): IResult
    {
        if (!$this->user->isInRole($this->role)) {
            return new Termination([ 'message' => 'Operation is not allowed' ], IResponse::S401_Unauthorized);
        }

        return $next($request);
    }
}