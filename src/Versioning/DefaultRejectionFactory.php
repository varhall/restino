<?php

namespace Varhall\Restino\Versioning;

use Varhall\Restino\Results\IResult;

/**
 * Default rejection: { "error": "client_outdated", "min_version": N, "current_version": M }
 */
class DefaultRejectionFactory implements RejectionFactory
{
    public function create(ClientVersion $version, int $required): IResult
    {
        $settings = $version->getSettings();

        return new ClientOutdated([
            'error'             => 'client_outdated',
            'min_version'       => $required,
            'current_version'   => $settings->current,
        ], $settings->rejectionCode);
    }
}
