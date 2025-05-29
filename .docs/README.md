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

## Usage

### Routing

At first, it is necessary to define routes for your API. Router `AttributeRouter` searches for controllers.
`Schema` object stores all the information about your API endpoints, such as HTTP methods, paths, parameters, etc.
You can generate `Schema` object using `SchemaGenerator` from PHP attributes.

    namespace App\Router;

    use Nette\Application\Routers\RouteList;
    use Varhall\Restino\Router\ApiRouter;
    use Varhall\Restino\Schema\SchemaGenerator;

    final class RouterFactory
    {
        public function __construct(private SchemaGenerator $schema)
        {
        }
        
        public function create(): RouteList
        {
            $router = new RouteList();
    
            $router[] = new ApiRouter($this->schema->getSchema());
    
            return $router;
        }
    }

### Presenters

The endpoint of your API is represented by controller. In combination with `SchemaGenerator` PHP attributes
are needed to define the endpoint. The controller must implement `IController` interface.

    <?php

    namespace App\Presenters;

    use App\Models\User;
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
    }


# More information

To learn more about Restino, check out the following topics:

- [Endpoints](endpoints.md) - Definition of API endpoints
- [Filters](filters.md) - Definition of API filters

