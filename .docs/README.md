# Restino

Restino is REST API framework for Nette Framework. It is based on standard Nette Presenters and tries
to make REST API development simple. It provides many features bundled in one package but also allows
to extend it with your own features.

## Setup

Enable Restino extension in config.neon file. You can also globally enable some bundled middlewares.

    extensions:
        restino: Varhall\Restino\DI\RestinoExtension

    restino:
        middlewares:
            cors: Varhall\Restino\Middlewares\Operations\CorsMiddleware
            collection: Varhall\Restino\Middlewares\Operations\CollectionMiddleware
            filter: Varhall\Restino\Middlewares\Operations\FilterMiddleware

## Usage

### Routing

At first, it is necessary to define routes for your API. You can use standard Nette routing but with `RestRoute`.

    $router[] = new RestRoute('api/<presenter>');

### Presenters

The endpoint of your API is represented by presenter. Bascially it is Nette presenter.

    <?php

    namespace App\Presenters;

    use App\Models\User;
    use Varhall\Restino\Controllers\RestController;
    use Varhall\Utilino\Collections\ICollection;

    class UsersPresenter extends RestController
    {
        public function index(): ICollection
        {
            return User::all();
        }
    }


# More information

To learn more about Dbino, check out the following topics:

- [Endpoints](endpoints.md) - Definition of API endpoints
- [Middlewares](middlewares.md) - Definition of API middlewares

