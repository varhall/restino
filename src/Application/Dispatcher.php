<?php

namespace Varhall\Restino\Application;

use Nette\Application\Responses\JsonResponse;
use Varhall\Restino\Controllers\ActionFactory;
use Varhall\Restino\Controllers\IController;
use Varhall\Restino\Controllers\RestRequest;
use Varhall\Restino\Filters\Context;
use Varhall\Restino\Filters\IFilter;
use Varhall\Restino\Filters\Chain;
use Varhall\Restino\Mapping\ValidationException;
use Varhall\Restino\Results\Abort;
use Varhall\Restino\Results\CollectionResult;
use Varhall\Restino\Results\IResult;
use Varhall\Restino\Results\SimpleResult;
use Varhall\Restino\Results\Termination;
use Varhall\Restino\Schema\Endpoint;
use Nette\Application\IPresenter;
use Nette\Application\Request;
use Nette\Application\Response;
use Nette\DI\Container;
use Nette\Http\Request as HttpRequest;
use Nette\Http\Response as HttpResponse;
use Varhall\Utilino\Collections\ICollection;

class Dispatcher implements IPresenter
{
    protected HttpRequest $httpRequest;
    protected HttpResponse $httpResponse;
    protected Container $container;
    protected ActionFactory $actionFactory;
    protected Chain $filters;

    public function __construct(HttpRequest $httpRequest,
                                HttpResponse $httpResponse,
                                Container $container,
                                ActionFactory $actionFactory,
                                Chain $filters
    ) {
        $this->httpRequest = $httpRequest;
        $this->httpResponse = $httpResponse;
        $this->container = $container;
        $this->actionFactory = $actionFactory;
        $this->filters = $filters;
    }

    public function run(Request $request): Response
    {
        $endpoint = $this->getEndpoint($request);
        $controller = $this->createController($endpoint);

        // chain attribute middlewares

        foreach ($this->getFilterAttributes($endpoint) as $attribute) {
            $instance = $attribute->newInstance();
            $name = 'attribute__' . $attribute->getName() . '_' . $attribute->getTarget();
            $this->filters->add($name, $instance);
        }

        // run setup

        if (method_exists($controller, 'setup')) {
            $controller->setup();
        }


        // run controller action

        $run = function(Context $context) use ($controller, $endpoint): IResult {
            try {
                $action = $this->actionFactory->create(
                    new \ReflectionMethod($controller, $endpoint->action),
                    $context->getRequest()
                );

                // run action
                $output = $action($controller);
                return $this->createResult($output);

            } catch (ValidationException $ex) {
                return new Termination($ex->errors);

            } catch (Abort $abort) {
                return $abort->getResult();
            }
        };

        // build filters chain a run

        $method = $this->filters->build($run, $endpoint->action);
        $context = new Context(
            $this->container,
            new RestRequest($request),
        );

        $result = $method($context);

        return new JsonResponse($result->execute($this->httpResponse, $this->httpRequest));
    }

    public function getEndpoint(Request $request): Endpoint
    {
        $endpoint = $request->getParameter('_endpoint');

        if (!$endpoint || !($endpoint instanceof Endpoint)) {
            throw new \Nette\InvalidStateException('Endpoint parameter is missing or invalid.');
        }

        return $endpoint;
    }

    public function createController(Endpoint $endpoint): IController
    {
        // TODO: perhaps check base type
        return $this->container->getByType($endpoint->controller);
    }

    public function getFilterAttributes(Endpoint $endpoint): array
    {
        $controller = new \ReflectionClass($endpoint->controller);
        $method = new \ReflectionMethod($endpoint->controller, $endpoint->action);

        return array_merge(
            $controller->getAttributes(IFilter::class, \ReflectionAttribute::IS_INSTANCEOF),
            $method->getAttributes(IFilter::class, \ReflectionAttribute::IS_INSTANCEOF)
        );
//
//        // map to ['className' => ReflectionAttribute] to make unique attributes
//        $attributes = array_combine(
//            array_map(fn($a) => $a->getName(), $attributes),
//            $attributes
//        );
//
//        return array_values($attributes);
    }

    public function createResult(mixed $data): IResult
    {
        if ($data instanceof IResult) {
            return $data;

        } else if ($data instanceof ICollection || $data instanceof \Nette\Database\Table\Selection) {
            return new CollectionResult($data);
        }

        return new SimpleResult($data);
    }
}