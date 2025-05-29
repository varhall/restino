<?php

namespace Varhall\Restino\Filters;

use Nette\Http\IResponse;
use Nette\Security\User;
use Varhall\Restino\Results\IResult;
use Varhall\Restino\Results\Termination;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Role implements IFilter
{
    protected string $role;

    public function __construct(string $role)
    {
        $this->role = $role;
    }

    public function execute(Context $context, callable $next): IResult
    {
        $user = $context->getContainer()->getByType(User::class);

        if (!$user->isInRole($this->role)) {
            return new Termination([ 'message' => 'Operation is not allowed' ], IResponse::S401_Unauthorized);
        }

        return $next($context);
    }
}