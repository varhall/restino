<?php

namespace Varhall\Restino\Versioning;

use Nette\Application\UI\Presenter;
use Nette\DI\Container;
use Nette\InvalidStateException;
use Varhall\Restino\Schema\Endpoint;
use Varhall\Restino\Versioning\Attributes\Downgrade;

/**
 * Applies Upgrade and Downgrade adapters according to the client version
 */
class Compatibility
{
    /** request parameters added by router, adapters never see them */
    public const INTERNAL_PARAMETERS = [ '_endpoint', 'action', Presenter::PresenterKey ];

    protected Metadata $metadata;

    /** @var array<string, object> */
    private array $adapters = [];

    public function __construct(
        protected ClientVersion $version,
        protected ?Container $container = null,
        ?Metadata $metadata = null,
    ) {
        $this->metadata = $metadata ?? new Metadata();
    }

    public function getClientVersion(): ClientVersion
    {
        return $this->version;
    }

    public function getSettings(): Settings
    {
        return $this->version->getSettings();
    }

    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    /**
     * Output has to be converted (client speaks older contract than current)
     */
    public function isActive(): bool
    {
        return $this->version->get() < $this->getSettings()->current;
    }

    /**
     * Required client version for the endpoint
     */
    public function requiredVersion(Endpoint $endpoint): int
    {
        return max($this->getSettings()->minimum, $this->metadata->minVersion($endpoint) ?? 0);
    }

    public function isUnversioned(Endpoint $endpoint): bool
    {
        return $this->metadata->isUnversioned($endpoint);
    }

    /**
     * Applies Upgrade adapters of the action in ascending order
     */
    public function upgrade(Endpoint $endpoint, array $parameters): array
    {
        $upgrades = array_filter(
            $this->metadata->upgrades($endpoint->controller, $endpoint->action),
            fn($upgrade) => $this->version->before($upgrade->before)
        );

        if (empty($upgrades)) {
            return $parameters;
        }

        $internal = array_intersect_key($parameters, array_flip(self::INTERNAL_PARAMETERS));
        $data = array_diff_key($parameters, $internal);

        foreach ($upgrades as $upgrade) {
            $data = $this->adapter($upgrade->with, Upgrader::class)($data);
        }

        return array_merge($data, $internal);
    }

    /**
     * Applies Downgrade adapters of the action on the whole serialized output in descending order
     */
    public function downgradeAction(Endpoint $endpoint, mixed $payload, mixed $source = null): mixed
    {
        if (!is_array($payload)) {
            return $payload;
        }

        foreach ($this->metadata->actionDowngrades($endpoint->controller, $endpoint->action) as $downgrade) {
            if ($this->version->before($downgrade->before)) {
                $payload = $this->adapter($downgrade->with, Downgrader::class)($payload, $source);
            }
        }

        return $payload;
    }

    /**
     * Downgrades tree of nodes step by step from current version to client version and returns plain data.
     *
     * In every step n (current ... client + 1) the tree is walked top-down and adapters Downgrade(before: n)
     * are applied. Each adapter therefore sees its object and children exactly in shape n.
     */
    public function downgrade(mixed $tree): mixed
    {
        for ($n = $this->getSettings()->current; $n > $this->version->get(); $n--) {
            $tree = $this->step($tree, $n);
        }

        return self::flatten($tree);
    }

    public static function flatten(mixed $value): mixed
    {
        if ($value instanceof Node) {
            return array_map([ self::class, 'flatten' ], $value->data);
        }

        if (is_array($value)) {
            return array_map([ self::class, 'flatten' ], $value);
        }

        return $value;
    }

    protected function step(mixed $value, int $version): mixed
    {
        if (is_array($value)) {
            return array_map(fn($item) => $this->step($item, $version), $value);
        }

        if (!($value instanceof Node)) {
            return $value;
        }

        // parent first (top-down) - adapter of shape n expects children in shape n as well
        foreach ($this->metadata->classDowngrades($value->class) as $downgrade) {
            if ($downgrade->before === $version) {
                $value->data = $this->applyDowngrade($downgrade, $value);
            }
        }

        $value->data = array_map(fn($item) => $this->step($item, $version), $value->data);

        return $value;
    }

    /**
     * Adapter gets plain data; unchanged children are put back as nodes so their own adapters
     * are applied in this and the next steps. Children modified by the adapter become plain data.
     */
    protected function applyDowngrade(Downgrade $downgrade, Node $node): array
    {
        $flat = array_map([ self::class, 'flatten' ], $node->data);
        $result = $this->adapter($downgrade->with, Downgrader::class)($flat, $node->source);

        $used = [];

        foreach ($result as $key => $value) {
            if (!is_array($value)) {
                continue;
            }

            $original = null;

            if (array_key_exists($key, $flat) && $flat[$key] === $value && !isset($used[$key])) {
                $original = $key;

            } else {
                foreach ($flat as $k => $v) {
                    if (!isset($used[$k]) && $v === $value && $node->data[$k] !== $v) {
                        $original = $k;
                        break;
                    }
                }
            }

            if ($original !== null) {
                $result[$key] = $node->data[$original];
                $used[$original] = true;
            }
        }

        return $result;
    }

    /**
     * @template T of object
     * @param class-string<T> $type
     * @return T
     */
    protected function adapter(string $class, string $type): object
    {
        if (!isset($this->adapters[$class])) {
            if (!class_exists($class)) {
                throw new InvalidStateException("Versioning adapter {$class} does not exist.");
            }

            $this->adapters[$class] = $this->container ? $this->container->createInstance($class) : new $class();
        }

        $adapter = $this->adapters[$class];

        if (!($adapter instanceof $type)) {
            throw new InvalidStateException("Versioning adapter {$class} must implement {$type}.");
        }

        return $adapter;
    }
}
