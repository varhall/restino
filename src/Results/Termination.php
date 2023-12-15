<?php

namespace Varhall\Restino\Results;


use Nette\Http\IResponse;

class Termination implements IResult
{
    protected mixed $data;
    protected int $code;

    public function __construct(mixed $data, int $code = \Nette\Http\Response::S400_BadRequest)
    {
        $this->data = $data;
        $this->code = $code;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function execute(IResponse $http): mixed
    {
        $http->setCode($this->code);
        return $this->data;
    }
}
