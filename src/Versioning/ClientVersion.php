<?php

namespace Varhall\Restino\Versioning;

use Nette\Http\IRequest;

/**
 * Version of the API contract the current client speaks.
 *
 * The version is resolved by ApiRouter (or Dispatcher) for every API request. Until then (CLI, queues,
 * tests of services) the current version is used, so nothing is downgraded outside of API requests.
 *
 * Version 0 means unknown client - missing version with "reject" policy or invalid value.
 * It is always below minimum.
 */
class ClientVersion
{
    public const UNKNOWN = 0;

    protected bool $resolved = false;

    protected ?int $declared = null;

    protected int $version;

    public function __construct(
        protected Settings $settings,
        protected VersionResolver $resolver,
    ) {
        $this->version = $settings->current;
    }

    /**
     * Resolves version from the request. Every call overwrites the previous state.
     */
    public function resolve(IRequest $request): void
    {
        $this->declared = $this->resolver->resolve($request);
        $this->version = $this->declared ?? $this->missing();
        $this->resolved = true;
    }

    /**
     * Sets the version manually (CLI commands, queue workers, tests)
     */
    public function set(int $version): void
    {
        $this->declared = $version;
        $this->version = $version;
        $this->resolved = true;
    }

    /**
     * Returns to the initial state (current version, not resolved)
     */
    public function reset(): void
    {
        $this->declared = null;
        $this->version = $this->settings->current;
        $this->resolved = false;
    }

    public function isResolved(): bool
    {
        return $this->resolved;
    }

    /**
     * Effective version (after applying missing version policy)
     */
    public function get(): int
    {
        return $this->version;
    }

    /**
     * Client speaks older contract than given version
     */
    public function before(int $version): bool
    {
        return $this->version < $version;
    }

    /**
     * Version which the client really sent (null = not sent, 0 = invalid value)
     */
    public function declared(): ?int
    {
        return $this->declared;
    }

    public function getSettings(): Settings
    {
        return $this->settings;
    }

    protected function missing(): int
    {
        return match ($this->settings->missing) {
            Settings::MISSING_CURRENT => $this->settings->current,
            Settings::MISSING_MINIMUM => $this->settings->minimum,
            default => self::UNKNOWN,
        };
    }
}
