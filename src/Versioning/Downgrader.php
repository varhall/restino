<?php

namespace Varhall\Restino\Versioning;

/**
 * Converts serialized data from shape (n) to shape (n - 1)
 */
interface Downgrader
{
    /**
     * @param array<mixed> $data serialized data in shape (n)
     * @param mixed $source original object (response class, model) or the action output
     * @return array<mixed>
     */
    public function __invoke(array $data, mixed $source): array;
}
