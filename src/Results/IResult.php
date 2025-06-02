<?php

namespace Varhall\Restino\Results;

use Nette\Http\IRequest;
use Nette\Http\IResponse;

interface IResult
{
    public function getData(): mixed;

    public function execute(IResponse $response, IRequest $request): mixed;
}