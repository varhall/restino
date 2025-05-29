<?php

namespace Varhall\Restino\DI;

use Nette\DI\CompilerExtension;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Varhall\Restino\Filters\Chain;

class RestinoExtension extends CompilerExtension
{
    public function getConfigSchema(): Schema
    {
        return Expect::structure([
            'filters' => Expect::arrayOf(Expect::string(), Expect::string())
        ]);
    }

    public function loadConfiguration()
    {
        $this->compiler->loadDefinitionsFromConfig(
            $this->loadFromFile(__DIR__ . '/restino.neon')['services'],
        );
    }

    public function beforeCompile()
    {
        $container = $this->getContainerBuilder();
        $chain = $container->getDefinitionByType(Chain::class);

        foreach ($this->config->filters as $name => $filter) {
            $chain->addSetup('add', [ $name, $filter ]);
        }
    }
}