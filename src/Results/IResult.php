<?php

namespace Varhall\Restino\Results;

use Nette\Http\IResponse;

interface IResult
{
    public function getData(): mixed;

    public function execute(IResponse $http): mixed;
}