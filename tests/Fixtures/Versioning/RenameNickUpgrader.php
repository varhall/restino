<?php

namespace Tests\Fixtures\Versioning;

use Varhall\Restino\Versioning\Upgrader;

/**
 * Version 1 sent { nick }, version 2 expects { name }
 */
class RenameNickUpgrader implements Upgrader
{
    public function __invoke(array $parameters): array
    {
        if (isset($parameters["nick"])) {
            $parameters["name"] = $parameters["nick"];
            unset($parameters["nick"]);
        }

        return $parameters;
    }
}
