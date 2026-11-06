<?php

namespace Varhall\Restino\Versioning;

use Varhall\Restino\Filters\CorsHeaderProvider;

/**
 * Adds version request header and advertised response headers to the Cors filter
 */
class VersioningCorsHeaders implements CorsHeaderProvider
{
    public function __construct(
        protected Settings $settings,
        protected VersionResolver $resolver,
    ) {
    }

    public function allowHeaders(): array
    {
        return $this->resolver->varyHeaders();
    }

    public function exposeHeaders(): array
    {
        return $this->settings->advertisedHeaders();
    }
}
