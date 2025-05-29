<?php

namespace Varhall\Restino\Mapping;

class ValidationException extends \Nette\InvalidArgumentException
{
    public array $errors;

    public function __construct(array $errors)
    {
        parent::__construct('Validation failed');

        $this->errors = $errors;
    }
}