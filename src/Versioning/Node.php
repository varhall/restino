<?php

namespace Varhall\Restino\Versioning;

/**
 * Serialized object in the intermediate tree used for step-by-step downgrade.
 * Values of $data can contain nested nodes.
 *
 * @internal
 */
final class Node
{
    public function __construct(
        public readonly string $class,
        public readonly object $source,
        public array $data,
    ) {
    }
}
