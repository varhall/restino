<?php

namespace Varhall\Restino\Versioning;

use Nette\InvalidArgumentException;

/**
 * Versioning configuration (section restino.versioning)
 */
final class Settings
{
    public const MISSING_REJECT = 'reject';
    public const MISSING_MINIMUM = 'minimum';
    public const MISSING_CURRENT = 'current';

    public function __construct(
        public readonly int $current,
        public readonly int $minimum,
        public readonly string $missing = self::MISSING_REJECT,
        public readonly int $rejectionCode = 426,
        public readonly ?string $advertiseMinimum = 'X-Api-Min-Version',
        public readonly ?string $advertiseCurrent = 'X-Api-Current-Version',
        public readonly bool $vary = true,
    ) {
        if ($minimum < 1) {
            throw new InvalidArgumentException("Minimum version must be at least 1, {$minimum} given.");
        }

        if ($minimum > $current) {
            throw new InvalidArgumentException("Minimum version ({$minimum}) cannot be greater than current version ({$current}).");
        }

        if (!in_array($missing, [ self::MISSING_REJECT, self::MISSING_MINIMUM, self::MISSING_CURRENT ], true)) {
            throw new InvalidArgumentException("Invalid missing version policy '{$missing}'.");
        }
    }

    /** @return string[] response headers advertising supported versions */
    public function advertisedHeaders(): array
    {
        return array_values(array_filter([ $this->advertiseMinimum, $this->advertiseCurrent ]));
    }
}
