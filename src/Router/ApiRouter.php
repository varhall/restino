<?php

namespace Varhall\Restino\Router;

use Varhall\Restino\Schema\Endpoint;
use Varhall\Restino\Schema\Schema;
use Nette\Application\UI\Presenter;
use Nette\Http\IRequest;
use Nette\Http\UrlScript;
use Nette\NotSupportedException;
use Nette\Routing\Router;
use Varhall\Restino\Versioning\ClientVersion;

class ApiRouter implements Router
{
    protected Schema $schema;

    protected ?ClientVersion $clientVersion;

    public function __construct(Schema $schema, ?ClientVersion $clientVersion = null)
    {
        $this->schema = $schema;
        $this->clientVersion = $clientVersion;
    }


    public function match(IRequest $httpRequest): ?array
    {
        $path = trim($httpRequest->getUrl()->getRelativePath(), '/');
        $path = (string) preg_replace('#/+#', '/', "/{$path}");

        // router runs before filters, so it resolves the client version itself
        $this->clientVersion?->resolve($httpRequest);

        foreach ($this->schema->groups as $group) {
            foreach ($group->endpoints as $endpoint) {
                $match = $this->matchEndpoint($path, $httpRequest->getMethod(), $endpoint);

                if ($match !== null) {
                    $data = json_decode($httpRequest->getRawBody() ?? '', true) ?? [];
                    return array_merge($match, $httpRequest->getQuery(), $data, [
                        Presenter::PresenterKey => 'Restino:' . $endpoint->controller,
                        'action'    => $endpoint->action,
                        '_endpoint' => $endpoint
                    ]);
                }
            }
        }

        return null;
    }

    public function constructUrl(array $params, UrlScript $refUrl): ?string
    {
        return null;
    }

    protected function matchEndpoint(string $path, string $method, Endpoint $endpoint): ?array
    {
        if ($this->clientVersion && !$endpoint->accepts($this->clientVersion->get())) {
            return null;
        }

        if ($endpoint->method === $method && preg_match("#^{$endpoint->getPattern()}$#", $path, $matches)) {
            return array_filter($matches, function ($key) {
                return !is_int($key);
            }, ARRAY_FILTER_USE_KEY);
        }

        return null;
    }
}
