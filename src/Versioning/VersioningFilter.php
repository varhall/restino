<?php

namespace Varhall\Restino\Versioning;

use Nette\Http\IResponse;
use Varhall\Restino\Filters\Context;
use Varhall\Restino\Filters\IFilter;
use Varhall\Restino\Results\IResult;

/**
 * Checks minimum client version, sets advertised and Vary headers and upgrades input parameters
 */
class VersioningFilter implements IFilter
{
    public function __construct(
        protected Compatibility $compatibility,
        protected VersionResolver $resolver,
        protected RejectionFactory $rejection,
    ) {
    }

    public function execute(Context $context, callable $next): IResult
    {
        $request = $context->getRequest();

        // CORS preflight never carries custom headers
        if ($request->getRequest()->getMethod() === 'OPTIONS') {
            return $next($context);
        }

        $endpoint = $context->getEndpoint();

        if ($this->compatibility->isUnversioned($endpoint)) {
            return $next($context);
        }

        $version = $this->compatibility->getClientVersion();
        $this->setHeaders($context->getHttpResponse());

        $required = $this->compatibility->requiredVersion($endpoint);

        if ($version->get() < $required) {
            return $this->rejection->create($version, $required);
        }

        $request->setParameters($this->compatibility->upgrade($endpoint, $request->getParameters()));

        return $next($context);
    }

    protected function setHeaders(IResponse $response): void
    {
        $settings = $this->compatibility->getSettings();

        if ($settings->advertiseMinimum) {
            $response->setHeader($settings->advertiseMinimum, (string) $settings->minimum);
        }

        if ($settings->advertiseCurrent) {
            $response->setHeader($settings->advertiseCurrent, (string) $settings->current);
        }

        if ($settings->vary) {
            $existing = array_filter(array_map('trim', explode(',', (string) $response->getHeader('Vary'))));
            $lower = array_map('strtolower', $existing);

            foreach ($this->resolver->varyHeaders() as $header) {
                if (!in_array(strtolower($header), $lower, true)) {
                    $existing[] = $header;
                    $lower[] = strtolower($header);
                }
            }

            if (!empty($existing)) {
                $response->setHeader('Vary', implode(', ', $existing));
            }
        }
    }
}
