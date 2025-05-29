<?php

namespace Varhall\Restino\Filters;

use Nette\Http\IResponse;
use Nette\Security\User;
use Varhall\Restino\Results\IResult;
use Varhall\Restino\Results\Termination;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Secured implements IFilter
{
    public function execute(Context $context, callable $next): IResult
    {
        $user = $context->getContainer()->getByType(User::class);

        if (!$user->isLoggedIn()) {
            return new Termination([ 'message' => 'User is not authenticated' ], IResponse::S401_Unauthorized);
        }

        return $next($context);
    }
}