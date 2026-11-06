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
use Varhall\Restino\Results\AbstractResult;
use Varhall\Restino\Results\CollectionResult;
use Varhall\Restino\Results\IResult;
use Varhall\Restino\Results\Serializer;
use Varhall\Restino\Results\SimpleResult;
use Varhall\Restino\Results\Termination;
use Varhall\Restino\Schema\Endpoint;
use Nette\Application\IPresenter;
use Nette\Application\Request;
use Nette\Application\Response;
use Nette\DI\Container;
use Nette\Http\Request as HttpRequest;
use Nette\Http\Response as HttpResponse;
use Varhall\Restino\Versioning\Compatibility;
use Varhall\Utilino\Collections\ICollection;

class Dispatcher implements IPresenter
{
    protected HttpRequest $httpRequest;
    protected HttpResponse $httpResponse;
    protected Container $container;
    protected ActionFactory $actionFactory;
    protected Chain $filters;
    protected ?Serializer $serializer;
    protected ?Compatibility $compatibility;
    protected int $collectionDefaultLimit = CollectionResult::DEFAULT_LIMIT;
    protected ?int $collectionMaxLimit = CollectionResult::MAX_LIMIT;

    public function __construct(HttpRequest $httpRequest,
                                HttpResponse $httpResponse,
                                Container $container,
                                ActionFactory $actionFactory,
                                Chain $filters,
                                ?Serializer $serializer = null,
                                ?Compatibility $compatibility = null
    ) {
        $this->httpRequest = $httpRequest;
        $this->httpResponse = $httpResponse;
        $this->container = $container;
        $this->actionFactory = $actionFactory;
        $this->filters = $filters;
        $this->serializer = $serializer;
        $this->compatibility = $compatibility;
    }

    public function run(Request $request): Response
    {
        $endpoint = $this->getEndpoint($request);
        $controller = $this->createController($endpoint);

        // version is normally resolved by ApiRouter, this covers manually created routers
        $version = $this->compatibility?->getClientVersion();
        if ($version && !$version->isResolved()) {
            $version->resolve($this->httpRequest);
        }

        // controller action

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

        $attributeFilters = [];

        try {
            // chain attribute middlewares

            foreach ($this->getFilterAttributes($endpoint) as $attribute) {
                $instance = $attribute->newInstance();
                $name = 'attribute__' . $attribute->getName() . '_' . $attribute->getTarget();
                $this->filters->add($name, $instance);
                $attributeFilters[] = $name;
            }

            // run setup

            if (method_exists($controller, 'setup')) {
                $controller->setup();
            }

            // build filters chain

            $method = $this->filters->build($run, $endpoint->action);

        } finally {
            // attribute filters belong to this request only, the chain is a shared service
            // (another dispatch in the same process would fail on duplicate filter name)
            foreach ($attributeFilters as $name) {
                $this->filters->remove($name);
            }
        }

        $context = new Context(
            $this->container,
            new RestRequest($request),
            $endpoint
        );

        $result = $method($context);

        if ($this->serializer && $result instanceof AbstractResult) {
            $result->setSerializer($this->serializer);
        }

        $payload = $result->execute($this->httpResponse, $this->httpRequest);

        if ($this->compatibility && $result instanceof AbstractResult) {
            $payload = $this->compatibility->downgradeAction($endpoint, $payload, $result->getData());
        }

        return new JsonResponse($payload);
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
        $controller = $this->container->getByType($endpoint->controller);

        if (!($controller instanceof IController)) {
            throw new \Nette\InvalidStateException("Controller {$endpoint->controller} must implement " . IController::class . '.');
        }

        return $controller;
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

    /**
     * Page size of collections when the client does not request any and the maximal page size (null = unlimited)
     */
    public function setCollectionLimits(int $defaultLimit, ?int $maxLimit): void
    {
        $this->collectionDefaultLimit = $defaultLimit;
        $this->collectionMaxLimit = $maxLimit;
    }

    public function createResult(mixed $data): IResult
    {
        if ($data instanceof ICollection || $data instanceof \Nette\Database\Table\Selection) {
            $data = new CollectionResult($data);
        }

        if ($data instanceof CollectionResult) {
            // limits set by the action take precedence over the configuration
            return $data->hasLimits() ? $data : $data->setLimits($this->collectionDefaultLimit, $this->collectionMaxLimit);

        } else if ($data instanceof IResult) {
            return $data;
        }

        return new SimpleResult($data);
    }
}
