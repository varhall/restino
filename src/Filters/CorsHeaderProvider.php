<?php

namespace Varhall\Restino\Filters;

/**
 * Services implementing this interface contribute headers to every Cors filter in the application.
 * Useful for libraries and extensions which introduce custom request or response headers.
 */
interface CorsHeaderProvider
{
    /** @return string[] request headers added to Access-Control-Allow-Headers */
    public function allowHeaders(): array;

    /** @return string[] response headers added to Access-Control-Expose-Headers */
    public function exposeHeaders(): array;
}
