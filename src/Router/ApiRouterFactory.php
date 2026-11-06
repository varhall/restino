<?php

namespace Varhall\Restino\Router;

use Varhall\Restino\Schema\SchemaGenerator;
use Varhall\Restino\Versioning\ClientVersion;

/**
 * Creates ApiRouter from controller attributes. When versioning is enabled, the router
 * selects endpoints by client version (since / before).
 */
class ApiRouterFactory
{
    public function __construct(
        protected SchemaGenerator $generator,
        protected ?ClientVersion $clientVersion = null,
    ) {
    }

    public function create(): ApiRouter
    {
        return new ApiRouter($this->generator->getSchema(), $this->clientVersion);
    }
}
