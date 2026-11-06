<?php

namespace Tests\Fixtures\Versioning;

use Varhall\Restino\Versioning\Upgrader;

/**
 * Version 2 sent { name }, version 3 expects { firstName, lastName }
 */
class SplitNameUpgrader implements Upgrader
{
    public function __invoke(array $parameters): array
    {
        if (isset($parameters["name"])) {
            [ $parameters["firstName"], $parameters["lastName"] ] = array_pad(explode(" ", $parameters["name"], 2), 2, "");
            unset($parameters["name"]);
        }

        return $parameters;
    }
}
