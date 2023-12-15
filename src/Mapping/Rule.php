<?php

namespace Varhall\Restino\Mapping;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Rule
{
    protected string $rule;
    protected bool $required;

    public function __construct(string $rule, bool $required = true)
    {
        $this->rule = $rule;
        $this->required = $required;
    }

    public function getRule(): string
    {
        return $this->rule;
    }

    public function getRequired(): bool
    {
        return $this->required;
    }

    public function getBaseRule(): string
    {
        return explode(':', $this->getRule())[0];
    }
}