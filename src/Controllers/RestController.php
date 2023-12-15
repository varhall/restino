<?php

namespace Varhall\Restino\Controllers;

use Nette\Application\IPresenter;
use Nette\Application\Request;
use Nette\Application\Response;
use Nette\Application\Responses\JsonResponse;
use Nette\Database\Table\Selection;
use Nette\Http\IResponse;
use Varhall\Restino\Mapping\MappingService;
use Varhall\Restino\Mapping\Target;
use Varhall\Restino\Middlewares\Attributes\IMiddlewareAttribute;
use Varhall\Restino\Middlewares\Chain;
use Varhall\Restino\Middlewares\Factory;
use Varhall\Restino\Middlewares\MiddlewareManager;
use Varhall\Restino\Results\IResult;
use Varhall\Restino\Results\Result;
use Varhall\Restino\Results\Termination;

abstract class RestController implements IPresenter
{
    protected ?\Nette\DI\Container $context;
    protected ?\Nette\Routing\Router $router;
    protected \Nette\Http\IRequest $httpRequest;
    protected \Nette\Http\IResponse $httpResponse;
    protected ?\Nette\Security\User $user = null;

    protected MappingService $mapping;
    protected Factory $middlewareFactory;
    protected Chain $middlewares;

    protected RestRequest $request;

    public final function injectPrimary(
        ?\Nette\DI\Container $context,
        ?\Nette\Routing\Router $router,
        \Nette\Http\IRequest $httpRequest,
        \Nette\Http\IResponse $httpResponse,
        MappingService $mapping,
        Factory $middlewareFactory,
        Chain $middlewares,
        ?\Nette\Security\User $user = null,
    ) {
        $this->context = $context;
        $this->router = $router;
        $this->httpRequest = $httpRequest;
        $this->httpResponse = $httpResponse;
        $this->user = $user;

        $this->mapping = $mapping;
        $this->middlewareFactory = $middlewareFactory;
        $this->middlewares = $middlewares;
    }

    public function setup(): void
    {

    }

    public function run(Request $request): Response
    {
        $this->setup();
        $this->parseMiddlewareAttributes($request->getParameter('action'));
        $restRequest = new RestRequest($request);

        $method = $this->middlewares->chain(
            fn(RestRequest $request): mixed => $this->runAction($request),
            $request->getParameter('action')
        );

        $result = $method($restRequest);
        return new JsonResponse($result->execute($this->httpResponse));


        /*
        $reflection = new \ReflectionClass($this);
        $attr = [];
        do {
            $attr += $reflection->getAttributes();
        } while ($reflection = $reflection->getParentClass());

        dumpe(array_map(fn($x) => $x->getName(), $attr));
        */
    }

    private function runAction(RestRequest $request): IResult
    {
        $this->request = $request;
        $action = $request->getRequest()->getParameter('action');

        if (!method_exists($this, $action)) {
            throw new \InvalidArgumentException("Method {$action} does not exist");
        }

        // map request to action parameters (filter + validate)
        $method = new \ReflectionMethod($this, $action);

        $parameters = [];
        $errors = [];
        foreach ($method->getParameters() as $parameter) {
            try {
                $parameters[] = $this->mapping->process(new Target($parameter), $request);
            } catch (\Nette\Schema\ValidationException $ex) {
                $errors = array_merge($errors, $ex->getMessageObjects());
            }
        }

        // handle errors
        if (!empty($errors)) {
            $res = array_combine(
                array_map(fn($e) => implode('.', $e->path), $errors),
                array_map(fn($e) => $e->toString(), $errors)
            );

            return new Termination($res, IResponse::S400_BadRequest);
        }

        // create result
        return new Result($method->invokeArgs($this, $parameters));
    }

    private function parseMiddlewareAttributes(string $action): void
    {
        if (!method_exists($this, $action)) {
            throw new \InvalidArgumentException("Method {$action} does not exist");
        }

        $class = new \ReflectionClass($this);
        $method = new \ReflectionMethod($this, $action);

        $attributes = array_merge(
            $class->getAttributes(IMiddlewareAttribute::class, \ReflectionAttribute::IS_INSTANCEOF),
            $method->getAttributes(IMiddlewareAttribute::class, \ReflectionAttribute::IS_INSTANCEOF)
        );

        foreach ($attributes as $attribute) {
            $middleware = $attribute->newInstance()->middleware($this->middlewareFactory);
            $name = 'attribute__' . $attribute->getName();
            $this->middlewares->add($name, $middleware);
        }
    }
}

