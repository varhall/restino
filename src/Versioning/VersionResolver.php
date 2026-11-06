<?php

namespace Varhall\Restino\Versioning;

use Nette\Http\IRequest;

/**
 * Determines the API contract version declared by the client
 */
interface VersionResolver
{
    /**
     * @return int|null null = client did not declare any version, 0 = invalid value
     */
    public function resolve(IRequest $request): ?int;

    /**
     * @return string[] request headers the response depends on (used for the Vary header)
     */
    public function varyHeaders(): array;
}
