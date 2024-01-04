<?php

namespace Varhall\Restino\Router;

use Nette\Application\Request;
use Nette\Http\IRequest;
use Varhall\Restino\Controllers\Action;
use Varhall\Restino\Controllers\RestController;
use \Varhall\Restino\Controllers\Attributes\Action as ActionAttribute;
use Varhall\Restino\Mapping\MappingService;

class ActionRouter
{
    protected IRequest $httpRequest;
    protected MappingService $mapping;

    public function __construct(IRequest $httpRequest, MappingService $mapping)
    {
        $this->httpRequest = $httpRequest;
        $this->mapping = $mapping;
    }

    public function route(RestController $controller, Request &$request): ?Action
    {
        $routes = $this->getRoutes($controller, $request->getParameter('mask') ?? '');

        foreach ($routes as $route) {
            if ($match = $route->match($this->httpRequest)) {
                $request->setParameters(
                    array_filter($match, fn($key) => !in_array($key, [ 'module', 'presenter', 'action' ]), ARRAY_FILTER_USE_KEY)
                );

                return new Action($route->getFunction(), $controller, $this->mapping);
            }
        }

        return null;
    }

    protected function getRoutes(RestController $controller, string $mask): array
    {
        $routes = [];
        $reflectionClass = new \ReflectionClass(get_class($controller));

        // attribute actions routes
        foreach ($reflectionClass->getMethods() as $method) {
            $attributes = $method->getAttributes(ActionAttribute::class, \ReflectionAttribute::IS_INSTANCEOF);

            if ($attribute = array_shift($attributes)) {
                $attribute = $attribute->newInstance();
                $routes[] = new ActionRoute($attribute->method, "{$mask}{$attribute->path}", $method);
            }
        }

        // implicit actions routes
        $implicit = [
            'index'     => [ 'GET',     '/'     ],
            'get'       => [ 'GET',     '/<id>' ],
            'create'    => [ 'POST',    '/'     ],
            'update'    => [ 'PUT',     '/<id>' ],
            'delete'    => [ 'DELETE',  '/<id>' ],
        ];

        foreach ($implicit as $action => $route) {
            if (method_exists($controller, $action)) {
                list($method, $path) = $route;
                $routes[] = new ActionRoute($method, "{$mask}{$path}", $reflectionClass->getMethod($action));
            }
        }

        return $routes;
    }
}