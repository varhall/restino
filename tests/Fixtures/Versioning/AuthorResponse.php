<?php

namespace Tests\Fixtures\Versioning;

use Varhall\Restino\Versioning\Attributes\Downgrade;
use Varhall\Utilino\ISerializable;

/**
 * Shape 3: { firstName, lastName }
 * Shape 2: { name }
 * Shape 1: { fullName }
 */
#[Downgrade(before: 3, with: AuthorSplitNameDowngrader::class)]
#[Downgrade(before: 2, with: AuthorRenameDowngrader::class)]
class AuthorResponse implements ISerializable
{
    public function __construct(
        public string $firstName,
        public string $lastName,
    ) {
    }

    public function toArray(): array
    {
        return [ "firstName" => $this->firstName, "lastName" => $this->lastName ];
    }
}
