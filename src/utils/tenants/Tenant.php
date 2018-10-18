<?php

namespace Varhall\Restino\Utils\Tenants;

use Nette\Http\Request;

class Tenant
{
    /**
     * @var \Nette\DI\Container
     */
    protected static $container = NULL;

    public static function setContainer(\Nette\DI\Container $container)
    {
        self::$container = $container;
    }

    public static function identifier()
    {
        $httpPequest = self::$container->getByType(Request::class);

        $url = $httpPequest->getUrl();

        $domain = explode('.', $url->host);
        return !empty($domain) ? $domain[0] : NULL;
    }
}