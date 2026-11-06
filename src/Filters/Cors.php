<?php

namespace Varhall\Restino\Filters;

use Nette\DI\Container;
use Nette\Http\IResponse;
use Varhall\Restino\Results\IResult;
use Varhall\Restino\Results\Termination;

class Cors implements IFilter
{
    public array $options = [];

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    public function execute(Context $context, callable $next): IResult
    {
        $this->setHeaders($context->getHttpResponse(), $this->getProviders($context->getContainer()));

        if ($context->getRequest()->getRequest()->getMethod() === 'OPTIONS') {
            return new Termination('', IResponse::S200_OK);
        }

        return $next($context);
    }

    /**
     * @param CorsHeaderProvider[] $providers
     */
    protected function setHeaders(IResponse $response, array $providers = []): void
    {
        $allow = $this->options['allow_headers'] ?? [ 'Content-type', 'Authorization' ];
        $expose = $this->options['expose_headers'] ?? [];

        foreach ($providers as $provider) {
            $allow = $this->merge($allow, $provider->allowHeaders());
            $expose = $this->merge($expose, $provider->exposeHeaders());
        }

        $response->setHeader('Access-Control-Allow-Origin', $this->options['allow_origin'] ?? '*');
        $response->setHeader('Access-Control-Allow-Headers', join(', ', $allow));
        $response->setHeader('Access-Control-Allow-Methods', join(', ',
            $this->options['allow_methods'] ?? [ 'GET', 'POST', 'PUT', 'DELETE' ]
        ));

        if (!empty($expose)) {
            $response->setHeader('Access-Control-Expose-Headers', join(', ', $expose));
        }
    }

    /**
     * @return CorsHeaderProvider[]
     */
    protected function getProviders(Container $container): array
    {
        $providers = array_map(
            fn(string $name) => $container->getService($name),
            $container->findByType(CorsHeaderProvider::class)
        );

        return array_values(array_filter($providers, fn($provider) => $provider instanceof CorsHeaderProvider));
    }

    /**
     * Appends headers which are not present yet (case-insensitive)
     */
    private function merge(array $headers, array $additional): array
    {
        $existing = array_map('strtolower', $headers);

        foreach ($additional as $header) {
            if (!in_array(strtolower($header), $existing, true)) {
                $headers[] = $header;
                $existing[] = strtolower($header);
            }
        }

        return $headers;
    }
}
