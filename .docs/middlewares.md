# Middlewares

- [Introduction](#introduction)
- [Registering middlewares](#registering-middlewares)
    - [Global middlewares](#global-middlewares)
    - [Presenter middlewares](#presenter-middlewares)
    - [Middleware configuration](#middleware-configuration)
- [Collection middleware](#collection-middleware)
- [Filter middleware](#filter-middleware)
- [Expand middleware](#expand-middleware)
- [CORS middleware](#cors-middleware)
- [Authentication middleware](#authentication-middleware)
- [Role middleware](#role-middleware)
- [Closure middleware](#closure-middleware)
- [Custom middleware](#custom-middleware)
    - [Middleware parameters](#middleware-parameters)
    - [Terminating middleware](#terminating-middleware)
    - [Before and after middleware](#before-and-after-middleware)

<a name="introduction"></a>
## Introduction

Middleware provide a convenient mechanism for inspecting and filtering HTTP requests entering your 
application. For example, Restino includes a middleware that verifies the user of your application 
is authenticated. If the user is not authenticated, the middleware will result with unautorized HTTP 
code. However, if the user is authenticated, the middleware will allow the request to proceed further 
into the application.

Additional middleware can be written to perform a variety of tasks besides authentication. For example, 
a logging middleware might log all incoming requests to your application. There are several middleware 
included in the Restino. 

<a name="registering-middlewares"></a>
## Registering middlewares

Middlewares have to be registered before they can be used. The registration is done in `config.neon` file.

<a name="global-middlewares"></a>
### Global middlewares

Global registered middlewares are applied to all endpoints. They are registered in `config.neon` file.

    restino:
        middlewares:
            cors: Varhall\Restino\Middlewares\Operations\CorsMiddleware
            collection: Varhall\Restino\Middlewares\Operations\CollectionMiddleware
            filter: Varhall\Restino\Middlewares\Operations\FilterMiddleware

<a name="presenter-middlewares"></a>
### Presenter middlewares

Presenter middlewares are applied only to specific presenter. They are registered in presenter class.

    <?php

    namespace App\Presenters;

    use Varhall\Restino\Controllers\RestController;
    use Varhall\Restino\Middlewares\Operations\ExpandMiddleware;

    class UsersPresenter extends RestController
    {
        public function setup(): void
        {
            $this->middlewares->add('collection', new CollectionMiddleware());
        }
    }

<a name="middleware-configuration"></a>
#### Middleware configuration

Globally registered middlewares need be modified sometimes. The typical case is Authentication middleware which is registered
globally but for some endpoint it is necessary to disable it. The configuration is done in presenter class.

There are methods `only` and `except` which can be used to enable/disable the middleware on specific methods.

    <?php

    namespace App\Presenters;

    use Varhall\Restino\Controllers\RestController;
    use Varhall\Restino\Middlewares\Operations\ExpandMiddleware;

    class UsersPresenter extends RestController
    {
        public function setup(): void
        {
            $this->middlewares->get('authentication')->only('index')
        }
    }

Middleware configuration can be completely removed using `remove` method.

    <?php

    namespace App\Presenters;

    use Varhall\Restino\Controllers\RestController;
    use Varhall\Restino\Middlewares\Operations\ExpandMiddleware;

    class UsersPresenter extends RestController
    {
        public function setup(): void
        {
            $this->middlewares->get('authentication')->remove();
        }
    }

<a name="collection-middleware"></a>
## Collection middleware

Collection middleware is used to order or paginate the Collection result. It works with `Varhall\Utilino\Collections\ICollection`
or `Nette\Database\Table\Selection` result. It is automatically applied to all endpoints returning collection result.

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

There are special query string parameters `_limit`, `_offset` and `_order` to control the pagination and ordering.
Parameters `_limit` and `_offset` are numbers. `_order` parameter is name of column to be ordered by. If the value
starts with "-" the ordering is descending (e.g. `_order=-name`). Multiple columns can be used for ordering by separating
them by comma (e.g. `_order=name,-age`).

    GET /api/users?_limit=3&_offset=10&_order=name

Example result will be

    {
        "pagination": {
            "limit": 3
            "offset": {
                "current": 0,
                "next": 12,
                "previous": 7
            },
            "total": 45
        },
        "results": [
            {
                "id": 1,
                "name": "Lars",
                "surname": "Ulrich",
                "email": "ulrich@metallica.com",
                "age": 50
            },
            {
                "id": 2,
                "name": "Sebastian",
                "surname": "Winkler",
                "email": "winkler@krauten.de",
                "age": 44
            },
            {
                "id": 3,
                "name": "Ludwig",
                "surname": "König",
                "email": "lking@gmail.com",
                "age": 18
            }
        ]
    }

<a name="filter-middleware"></a>
## Filter middleware

Filter middleware is very sofiticated middleware which allows to filter the collection result. It works 
with `Varhall\Dbino\Collection` or `Nette\Database\Table\Selection` result. It filters the result according
to query string parameters.

    GET /api/users?name=Sebastian&age>=30

It causes that results with name Sebastian and age greater or equal to 30 are returned.

There are many operators which can be used for filtering.

| Operator | Description              | Example                          |
|----------|--------------------------|----------------------------------|
| `=`      | Equal to                 | `/api/users?name=Sebastian`      |
| `!=`     | Not equal to             | `/api/users?name!=Sebastian`     |
| `>`      | Greater than             | `/api/users?age>30`              |
| `>=`     | Greater than or equal to | `/api/users?age>=30`             |
| `<`      | Less than                | `/api/users?age<30`              |
| `<=`     | Less than or equal to    | `/api/users?age<=30`             |
| `*`      | LIKE                     | `/api/users?name=Seb*`           |
| `,`      | IN                       | `/api/users?name=Sebastian,Lars` |

<a name="expand-middleware"></a>
## Expand middleware

Expand middleware is another must have middleware. It allows to expand the result with related models or another extra 
value. It adds new property to the result with related model according to the expand definition and expand request in 
query string taken from parameter `_expand`.

Expand definition is defined in presenter class.

    <?php

    namespace App\Presenters;

    use App\Models\User;
    use Varhall\Restino\Controllers\RestController;
    use Varhall\Utilino\Collections\ICollection;

    class UsersPresenter extends RestController
    {
        public function setup(): void
        {
            $this->middlewares->add('expand', new ExpandMiddleware([
                'full_name' => function (User $user) {
                    return $user->name . ' ' . $user->surname;
                },
                'role'      => 'user_role',
                'address',
            ]));
        }
    }

It automatically calls custom expand function for property `full_name`. If the value is string, it tries
to call this method on returned model. If array key-value is not used, expanded property name is same called function.

Here is some example. Let's pretend the `User` class defined as:

    <?php

    namespace App\Models;

    use Varhall\Dbino\Model;

    class User extends Model
    {
        public function address(): Address
        {
            return $this->belongsTo(Address::class, 'address_id');
        }

        public function user_role(): Role
        {
            return $this->belongsTo(Role::class, 'role_id');
        }
    }

    GET /api/users/1?_expand=full_name,role,address

It causes that results are expanded with full_name, role and address model.

    {
        "id": 1,
        "name": "Lars",
        "surname": "Ulrich",
        "email": "ulrich@metallica.com",
        "full_name": "Lars Ulrich",
        "role": {
            "id": 1,
            "name": "Administrator"
        },
        "address": {
            "id": 1,
            "street": "Main Street",
            "city": "New York",
            "zip": "12345"
        }
    }

If the returned result is collection, the expand function is called for each item in collection.

    GET /api/users?_expand=full_name,role,address

<a name="cors-middleware"></a>
## CORS middleware

CORS middleware is used to return CORS headers. The default return values are:

    Access-Control-Allow-Origin: *
    Access-Control-Allow-Methods: GET, POST, PUT, DELETE
    Access-Control-Allow-Headers: Content-Type, Authorization

The default values can be changed using options array in constructor.

    <?php

    namespace App\Presenters;

    use Varhall\Restino\Controllers\RestController;
    use Varhall\Restino\Middlewares\Operations\CorsMiddleware;

    class UsersPresenter extends RestController
    {
        public function setup(): void
        {
            $this->middlewares->add('cors', new CorsMiddleware([
                'allow_origin'  => 'https://example.com',
                'allow_methods' => ['GET, 'POST', 'PUT', 'DELETE', 'OPTIONS'],
                'allow_headers' => ['Content-Type, 'Authorization', 'X-Api-Key'],
            ]));
        }
    }

<a name="authentication-middleware"></a>
## Authentication middleware

Authentication middleware is used to check if the user is authenticated. It uses standard Nette authentication
mechanism `Nette\Security\User` and its `isLoggedIn` method. If the user is not authenticated, the middleware
returns `401 Unauthorized` HTTP code.

<a name="role-middleware"></a>
## Role middleware

Role middleware is used to check if the user has required role. It uses standard Nette authentication
mechanism `Nette\Security\User` and its `isInRole` method. If the user has not required role, the middleware
returns `401 Unautorized` HTTP code.

It takes parameter `role` in constructor.

    <?php

    namespace App\Presenters;

    use Varhall\Restino\Controllers\RestController;
    use Varhall\Restino\Middlewares\Operations\RoleMiddleware;

    class UsersPresenter extends RestController
    {
        public function setup(): void
        {
            $this->middlewares->add('role', new RoleMiddleware('admin'));
        }
    }

<a name="closure-middleware"></a>
## Closure middleware

Closure middleware is used to call custom function.

    <?php

    namespace App\Presenters;

    use Varhall\Restino\Controllers\RestController;
    use Varhall\Restino\Controllers\RestRequest;
    use Varhall\Restino\Middlewares\Operations\ClosureMiddleware;

    class UsersPresenter extends RestController
    {
        public function setup(): void
        {
            $this->middlewares->add('my-middleware', function (RestRequest $request, callable $next) {
                // do something
            });
        }
    }

<a name="custom-middleware"></a>
## Custom middleware

You can define your own middleware. The middleware must implement `Varhall\Restino\Middlewares\IMiddleware` 
interface. The interface has only one method `__invoke` which takes `Varhall\Restino\Controllers\RestRequest`,
`callable` to call next middleware and returns `Varhall\Restino\Results\IResult`.

    <?php

    namespace App\Presenters;

    use Varhall\Restino\Controllers\RestController;
    use Varhall\Restino\Controllers\RestRequest;
    use Varhall\Restino\Middlewares\IMiddleware;

    class UsersPresenter extends RestController
    {
        public function setup(): void
        {
            $this->middlewares->add('my-middleware', new class implements IMiddleware {
                public function handle(RestRequest $request, callable $next): void
                {
                    // do something
                }
            });
        }
    }

<a name="middleware-parameters"></a>
### Middleware parameters

In most cases middleware needs to work with some extra parameters. The parameters can be passed to middleware
through constructor. The parameters can be defined in `config.neon` file.

    restino:
        middlewares:
            my-middleware: App\MyMiddleware(%myParameter%)

or in presenter class.

    <?php

    namespace App\Presenters;

    use Varhall\Restino\Controllers\RestController;
    use Varhall\Restino\Controllers\RestRequest;
    use Varhall\Restino\Middlewares\IMiddleware;

    class UsersPresenter extends RestController
    {
        public function setup(): void
        {
            $this->middlewares->add('my-middleware', new MyMiddleware('foo', 'bar'));
        }
    }

Middlewares can also be registered in standard Nette DI container as service. In this case the parameters
are passed automatically.

<a name="terminating-middleware"></a>
### Terminating middleware

Terminating middleware is used to terminate the request. It is used for example for authentication middleware
which terminates the request if the user is not authenticated. In case of early termination, the middleware
should return `Termination` object. Example usage could be

    public function __invoke(RestRequest $request, callable $next): IResult
    {
        if (!$this->user->isInRole($this->role)) {
            return new Termination([ 'message' => 'Operation is not allowed' ], IResponse::S401_Unauthorized);
        }

        return $next($request);
    }

<a name="before-and-after-middleware"></a>
### Before and after middleware

Before middleware handles the request before it is processed by controller. After middleware handles the request
after it is processed by controller. The before middleware is called in order they are registered. The after
middleware is called in reverse order they are registered.

The before/after usage depends on when `$next` is called. If `$next` is called before the custom code, the after
middleware is called before the custom code. If `$next` is called after the custom code, the after middleware
is called after the custom code.

    public function __invoke(RestRequest $request, callable $next): IResult
    {
        // before middleware

        $result = $next($request);

        // after middleware

        return $result;
    }

