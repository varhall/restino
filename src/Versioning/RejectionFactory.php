<?php

namespace Varhall\Restino\Versioning;

use Varhall\Restino\Results\IResult;

/**
 * Creates the response for clients below the required version
 */
interface RejectionFactory
{
    public function create(ClientVersion $version, int $required): IResult;
}
