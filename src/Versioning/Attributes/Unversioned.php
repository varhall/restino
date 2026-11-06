<?php

namespace Varhall\Restino\Versioning\Attributes;

/**
 * Endpoint is excluded from versioning - no version check, no advertised headers, no adapters for input.
 * Use for endpoints called by browsers or third parties (files, documentation, webhooks).
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Unversioned
{
}
