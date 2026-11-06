<?php

namespace Varhall\Restino\DI;

use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\InvalidConfigurationException;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Varhall\Restino\Filters\Chain;
use Varhall\Restino\Filters\Cors;
use Varhall\Restino\Results\CollectionResult;
use Varhall\Restino\Versioning\ClientVersion;
use Varhall\Restino\Versioning\Compatibility;
use Varhall\Restino\Versioning\DefaultRejectionFactory;
use Varhall\Restino\Versioning\HeaderVersionResolver;
use Varhall\Restino\Versioning\Inspection\Inventory;
use Varhall\Restino\Versioning\RejectionFactory;
use Varhall\Restino\Versioning\Settings;
use Varhall\Restino\Versioning\VersionResolver;
use Varhall\Restino\Versioning\VersioningCorsHeaders;
use Varhall\Restino\Versioning\VersioningFilter;

class RestinoExtension extends CompilerExtension
{
    public const VERSIONING_FILTER = 'versioning';

    public function getConfigSchema(): Schema
    {
        return Expect::structure([
            'filters' => Expect::arrayOf(Expect::string(), Expect::string()),
            'collection' => Expect::structure([
                'defaultLimit'  => Expect::int(CollectionResult::DEFAULT_LIMIT),
                'maxLimit'      => Expect::int(CollectionResult::MAX_LIMIT)->nullable(),
            ]),
            'versioning' => Expect::anyOf(null, Expect::structure([
                'current'   => Expect::int()->required(),
                'minimum'   => Expect::int()->required(),
                'header'    => Expect::string('X-Api-Version'),
                'missing'   => Expect::anyOf(Settings::MISSING_REJECT, Settings::MISSING_MINIMUM, Settings::MISSING_CURRENT)
                                    ->default(Settings::MISSING_REJECT),
                'rejection' => Expect::structure([
                    'code'      => Expect::int(426),
                ]),
                'advertise' => Expect::anyOf(
                    false,
                    Expect::structure([
                        'minimum'   => Expect::anyOf(Expect::string(), false)->default('X-Api-Min-Version'),
                        'current'   => Expect::anyOf(Expect::string(), false)->default('X-Api-Current-Version'),
                    ])
                )->default(null),
                'vary'      => Expect::bool(true),
                'position'  => Expect::structure([
                    'after'     => Expect::listOf(Expect::string())->default([ Cors::class ]),
                    'before'    => Expect::listOf(Expect::string())->default([]),
                ]),
            ]))->default(null),
        ]);
    }

    public function loadConfiguration()
    {
        $this->compiler->loadDefinitionsFromConfig(
            $this->loadFromFile(__DIR__ . '/restino.neon')['services'],
        );

        $config = $this->getSettings();

        $this->loadCollection($config->collection);

        if ($config->versioning) {
            $this->loadVersioning($config->versioning);
        }
    }

    public function beforeCompile()
    {
        $config = $this->getSettings();
        $container = $this->getContainerBuilder();
        $chain = $container->getDefinitionByType(Chain::class);
        assert($chain instanceof ServiceDefinition);

        if ($config->versioning) {
            // custom implementations registered by the application replace the default ones
            $this->removeDefaultIfOverridden(VersionResolver::class, $this->prefix('versioning.resolver'));
            $this->removeDefaultIfOverridden(RejectionFactory::class, $this->prefix('versioning.rejection'));

            $position = $config->versioning->position;

            $chain->addSetup('$service->add(?, ?)->after(?)->before(?)', [
                self::VERSIONING_FILTER,
                '@' . $this->prefix('versioning.filter'),
                $position->after,
                $position->before,
            ]);
        }

        foreach ($config->filters as $name => $filter) {
            $chain->addSetup('add', [ $name, $filter ]);
        }
    }

    /**
     * Normalized configuration (structure defined by getConfigSchema)
     */
    protected function getSettings(): \stdClass
    {
        assert($this->config instanceof \stdClass);
        return $this->config;
    }

    protected function loadCollection(\stdClass $config): void
    {
        if ($config->defaultLimit < 1 || ($config->maxLimit !== null && $config->maxLimit < $config->defaultLimit)) {
            throw new InvalidConfigurationException(
                "Option '{$this->name}› collection › defaultLimit' must be at least 1 and not greater than 'maxLimit', "
                . "{$config->defaultLimit} given (maxLimit " . ($config->maxLimit ?? 'null') . ').'
            );
        }

        $dispatcher = $this->getContainerBuilder()->getDefinition($this->prefix('dispatcher'));
        assert($dispatcher instanceof ServiceDefinition);
        $dispatcher->addSetup('setCollectionLimits', [ $config->defaultLimit, $config->maxLimit ]);
    }

    protected function loadVersioning(\stdClass $config): void
    {
        $builder = $this->getContainerBuilder();

        if ($config->minimum < 1 || $config->minimum > $config->current) {
            throw new InvalidConfigurationException(
                "Option '{$this->name}› versioning › minimum' must be between 1 and current version ({$config->current}), {$config->minimum} given."
            );
        }

        $advertise = $config->advertise === null
            ? (object) [ 'minimum' => 'X-Api-Min-Version', 'current' => 'X-Api-Current-Version' ]
            : $config->advertise;

        $builder->addDefinition($this->prefix('versioning.settings'))
            ->setFactory(Settings::class, [
                'current'           => $config->current,
                'minimum'           => $config->minimum,
                'missing'           => $config->missing,
                'rejectionCode'     => $config->rejection->code,
                'advertiseMinimum'  => $advertise ? ($advertise->minimum ?: null) : null,
                'advertiseCurrent'  => $advertise ? ($advertise->current ?: null) : null,
                'vary'              => $config->vary,
            ]);

        $builder->addDefinition($this->prefix('versioning.resolver'))
            ->setFactory(HeaderVersionResolver::class, [ $config->header ]);

        $builder->addDefinition($this->prefix('versioning.clientVersion'))
            ->setFactory(ClientVersion::class);

        $builder->addDefinition($this->prefix('versioning.compatibility'))
            ->setFactory(Compatibility::class);

        $builder->addDefinition($this->prefix('versioning.rejection'))
            ->setFactory(DefaultRejectionFactory::class);

        $builder->addDefinition($this->prefix('versioning.filter'))
            ->setFactory(VersioningFilter::class)
            ->setAutowired(false);

        $builder->addDefinition($this->prefix('versioning.cors'))
            ->setFactory(VersioningCorsHeaders::class);

        $builder->addDefinition($this->prefix('versioning.inventory'))
            ->setFactory(Inventory::class);
    }

    protected function removeDefaultIfOverridden(string $type, string $default): void
    {
        $builder = $this->getContainerBuilder();
        $services = array_keys($builder->findByType($type));

        if (count($services) > 1 && in_array($default, $services, true)) {
            $builder->removeDefinition($default);
        }
    }
}
