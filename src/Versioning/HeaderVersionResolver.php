<?php

namespace Varhall\Restino\Versioning;

use Nette\Http\IRequest;

/**
 * Reads version from HTTP header, e.g. X-Api-Version: 3
 */
class HeaderVersionResolver implements VersionResolver
{
    public function __construct(
        protected string $header = 'X-Api-Version'
    ) {
    }

    public function getHeader(): string
    {
        return $this->header;
    }

    public function resolve(IRequest $request): ?int
    {
        $value = $request->getHeader($this->header);

        if ($value === null) {
            return null;
        }

        return self::parse($value);
    }

    public function varyHeaders(): array
    {
        return [ $this->header ];
    }

    /**
     * Parses positive integer, any other value is invalid (0)
     */
    public static function parse(string $value): int
    {
        $value = trim($value);

        if (!preg_match('/^[0-9]{1,9}$/', $value)) {
            return 0;
        }

        return (int) $value;
    }
}
