<?php

namespace Varhall\Restino\Presenters\Results;

use Varhall\Restino\Presenters\RestRequest;

/**
 * API Method result
 *
 * @author Ondrej Sibrava <sibrava@varhall.cz>
 */
interface IResult
{
    public function run(RestRequest $request);
}
