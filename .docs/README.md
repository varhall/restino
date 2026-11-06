# Restino

Restino is REST API framework for Nette Framework. It is based on Nette Application and tries
to make REST API development super simple. It provides many features bundled in one package but 
also allows you to extend it with your own features.

## Installation

To install Restino, you can use Composer. Run the following command in your terminal:

    composer require varhall/restino

## Setup

Enable Restino extension in config.neon file. You can also globally enable some bundled filters.

    extensions:
        restino: Varhall\Restino\DI\RestinoExtension

    restino:
        filters:
            cors: Varhall\Restino\Filters\Cors
            collection: Varhall\Restino\Filters\Collection
            filter: Varhall\Restino\Filters\Filter

        # optional - serve old and new API clients at the same time, see versioning.md
        # versioning:
        #     current: 1
        #     minimum: 1

## Usage

### Routing

At first, it is necessary to define routes for your API. Router `ApiRouter` maps requests to controllers.
`Schema` object stores all the information about your API endpoints, such as HTTP methods, paths, parameters, etc.
`ApiRouterFactory` generates the `Schema` from PHP attributes of your controllers and creates the router.

    namespace App\Router;

    use Nette\Application\Routers\RouteList;
    use Varhall\Restino\Router\ApiRouterFactory;

    final class RouterFactory
    {
        public function __construct(private ApiRouterFactory $api)
        {
        }
        
        public function create(): RouteList
        {
            $router = new RouteList();
    
            $router[] = $this->api->create();
    
            return $router;
        }
    }

See [Routing](endpoints.md#routing) for other options.

### Controllers

The endpoints of your API are actions of controllers. A controller implements `IController` interface, PHP
attributes map its methods to HTTP methods and paths. Controllers must be registered as services (see
[Controller DI registration](endpoints.md#controller-di)).

    <?php

    namespace App\Controllers;

    use App\Models\User;
    use Varhall\Restino\Controllers\Attributes\Get;
    use Varhall\Restino\Controllers\Attributes\Path;
    use Varhall\Restino\Controllers\IController;
    use Varhall\Utilino\Collections\ICollection;

    #[Path('/api/users')]
    class UsersController implements IController
    {
        #[Get('/')]
        public function index(): ICollection
        {
            return User::all();
        }

        #[Get('/{id}')]
        public function detail(int $id): UserResponse
        {
            return new UserResponse(User::find($id));
        }
    }

    GET /api/users/1

    { "id": 1, "name": "Dave Murray" }


# More information

To learn more about Restino, check out the following topics:

- [Endpoints](endpoints.md) - Controllers, routing, input parameters and validation
- [Responses](results.md) - Return values, serialization, collections, status codes and errors
- [Filters](filters.md) - Middlewares: pagination, filtering, expansions, CORS, authentication and custom filters
- [Versioning](versioning.md) - Serving old and new API clients at the same time

