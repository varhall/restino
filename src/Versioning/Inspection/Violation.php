<?php

namespace Varhall\Restino\Versioning\Inspection;

/**
 * Versioning construct which is dead, redundant or invalid
 */
final class Violation
{
    public function __construct(
        public readonly string $class,
        public readonly ?string $method,
        /** @var class-string */
        public readonly string $attribute,
        public readonly string $message,
    ) {
    }

    public function getLocation(): string
    {
        return $this->method !== null ? "{$this->class}::{$this->method}()" : $this->class;
    }

    public function __toString(): string
    {
        $attribute = (new \ReflectionClass($this->attribute))->getShortName();
        return "{$this->getLocation()} #[{$attribute}]: {$this->message}";
    }
}
