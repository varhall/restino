<?php

namespace Varhall\Restino\DI;

/**
 * Description of RestinoExtension
 *
 * @author fero
 */
class RestinoExtension extends \Nette\DI\CompilerExtension
{
    protected function configuration()
    {
        return $this->getConfig();
    }

    public function afterCompile(\Nette\PhpGenerator\ClassType $class)
    {
        parent::afterCompile($class);

        $config = $this->configuration();

        // metoda initialize
        $initialize = $class->getMethod('initialize');

        $initialize->addBody('\Varhall\Restino\Utils\Tenants\Tenant::setContainer($this->getByType(?));', ['\Nette\DI\Container']);
    }
}
