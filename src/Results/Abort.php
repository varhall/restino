<?php

namespace Varhall\Restino\Results;

class Abort extends \Exception
{
    protected IResult $result;

    public function __construct(IResult $result)
    {
        parent::__construct();

        $this->result = $result;
    }

    public function getResult(): IResult
    {
        return $this->result;
    }
}