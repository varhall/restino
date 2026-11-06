<?php

namespace Varhall\Restino\Versioning;

use Varhall\Restino\Results\Termination;

/**
 * Client version is lower than the version required by the endpoint
 */
class ClientOutdated extends Termination
{
    public function getCode(): int
    {
        return $this->code;
    }
}
