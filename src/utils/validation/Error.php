<?php

namespace Varhall\Restino\Utils\Validation;

use Nette\Utils\Json;
use Varhall\Restino\Utils\Validation\Rules\Rule;
use Varhall\Utilino\ISerializable;

class Error implements ISerializable
{
    protected $message = null;
    protected $rule = null;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function setSource(Rule $rule)
    {
        $this->rule = $rule;
        return $this;
    }

    public function getType()
    {
        return (new \ReflectionClass($this->rule))->getShortName();
    }

    public function toArray()
    {
        return [ 'message' => $this->message, 'type' => $this->getType() ];
    }
    public function toJson()
    {
        return Json::encode($this->toArray());
    }
}