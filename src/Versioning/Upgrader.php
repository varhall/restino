<?php

namespace Varhall\Restino\Versioning;

/**
 * Converts request parameters (merged path, query and body) from shape (n - 1) to shape (n)
 */
interface Upgrader
{
    /**
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function __invoke(array $parameters): array;
}
