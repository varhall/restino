# Filters

- [Introduction](#introduction)
- [Registering filters](#registering-filters)
    - [Global filters](#global-filters)
    - [Endpoint filters](#endpoint-filters)
    - [Filter configuration](#filter-configuration)
- [Collection filter](#collection-filter)
- [Filter filter](#filter-filter)
- [Expand filter](#expand-filter)
- [CORS filter](#cors-filter)
- [Secured filter](#secured-filter)
- [Role filter](#role-filter)
- [Closure filter](#closure-filter)
- [Custom filter](#custom-filter)
    - [Filter parameters](#filter-parameters)
    - [Terminating filter](#terminating-filter)
    - [Request and response filter](#request-and-response-filter)
- [Filter attributes](#filter-attributes)
    - [Custom filter attributes](#custom-filter-attributes)

<a name="introduction"></a>
## Introduction

Filter provide a extension mechanism for inspecting and filtering API endpoints in the application. 
For example, Restino includes a filter that verifies the user of your application 
is authenticated. If the user is not authenticated, the filter will result with unautorized HTTP 
code. However, if the user is authenticated, the filter will allow the request to proceed further 
into the application.

Additional filter can be written to perform a variety of tasks besides authentication. For example, 
a logging filter might log all incoming requests to your application. There are several filter 
included in the Restino. 

<a name="registering-filters"></a>
## Registering filters

Filters have to be registered before they can be used. The registration is done in `config.neon` file.

<a name="global-filters"></a>
### Global filters

Global registered filters are applied to all endpoints. They are registered in `config.neon` file.

    restino:
        filters:
            cors: Varhall\Restino\Filters\Cors
		    collection: Varhall\Restino\Filters\Collection
		    filter: Varhall\Restino\Filters\Filter

<a name="endpoint-filters"></a>
### Endpoint filters

Endpoint filters are applied only to specific action. They are registered using PHP attributes to the
action or in controller class. Filters can be also registered or modified in `setup` method.

    <?php

    namespace App\Controllers;

    use VarhallRestinoControllersIController;
    use Varhall\Restino\Controllers\Attributes;
    use Varhall\Restino\Filters\Expand;
    use Varhall\Restino\Filters\Chain;

    #[Path('/api/users')]
    class UsersController implements IController
    {
        public function __construct(private Chain $filters) {}

        public function setup(): void
        {
            $this->filters->add('expand', new Expand($this->expandDefinition()));
        }

        public function expandDefinition(): array
        {
            return [];
        }
    }

<a name="filter-configuration"></a>
#### Filter configuration

Globally registered filters need be modified sometimes. The typical case is Authentication filter which is registered
globally but for some endpoint it is necessary to disable it. The configuration is done in presenter class.

There are methods `only` and `except` which can be used to enable/disable the filter on specific methods.

    <?php

    namespace App\Controllers;

    use VarhallRestinoControllersIController;
    use Varhall\Restino\Controllers\Attributes;
    use Varhall\Restino\Filters\Chain;

    #[Path('/api/users')]
    class UsersController implements IController
    {
        public function __construct(private Chain $filters) {}

        public function setup(): void
        {
            $this->filters->get('authentication')->only('index')
        }
    }

Filter configuration can be completely removed using `remove` method.

    <?php

    namespace App\Controllers;

    use VarhallRestinoControllersIController;
    use Varhall\Restino\Controllers\Attributes;
    use Varhall\Restino\Filters\Chain;

    #[Path('/api/users')]
    class UsersController implements IController
    {
        public function __construct(private Chain $filters) {}

        public function setup(): void
        {
            $this->filters->get('authentication')->remove();
        }
    }

<a name="collection-filter"></a>
## Collection filter

`Collection` filter is used to order or paginate the Collection result. It works with `Varhall\Utilino\Collections\ICollection`
or `Nette\Database\Table\Selection` result. It is automatically applied to all endpoints returning collection result.

    <?php

    namespace App\Controllers;

    use App\Models\User;
    use VarhallRestinoControllersIController;
    use Varhall\Restino\Controllers\Attributes;
    use Varhall\Utilino\Collections\ICollection;

    #[Path('/api/users')]
    class UsersController implements IController
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

<a name="filter-filter"></a>
## Filter filter

`Filter` is very sofiticated filter which allows to filter the collection result. It works 
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

<a name="expand-filter"></a>
## Expand filter

`Expand` filter is another must have filter. It allows to expand the result with related models or another extra 
value. It adds new property to the result with related model according to the expand definition and expand request in 
query string taken from parameter `_expand`.

Expand definition is defined in presenter class.

    <?php

    namespace App\Controllers;

    use App\Models\User;
    use VarhallRestinoControllersIController;
    use Varhall\Restino\Controllers\Attributes;
    use Varhall\Utilino\Collections\ICollection;
    use Varhall\Restino\Filters\Chain;

    #[Path('/api/users')]
    class UsersController implements IController
    {
        public function __construct(private Chain $filters) {}

        public function setup(): void
        {
            $this->filters->add('expand', new Expand([
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
        "name": "Dave",
        "surname": "Murray",
        "email": "murray@ironmaiden.com",
        "full_name": "Dave Murray",
        "role": {
            "id": 1,
            "name": "Guitar"
        },
        "address": {
            "id": 1,
            "street": "Main Street",
            "city": "London",
            "zip": "E1 7HX"
        }
    }

If the returned result is collection, the expand function is called for each item in collection.

    GET /api/users?_expand=full_name,role,address

<a name="cors-filter"></a>
## CORS filter

`Cors` filter is used to return CORS headers. The default return values are:

    Access-Control-Allow-Origin: *
    Access-Control-Allow-Methods: GET, POST, PUT, DELETE
    Access-Control-Allow-Headers: Content-Type, Authorization

The default values can be changed using options array in constructor.

    <?php

    namespace App\Controllers;

    use VarhallRestinoControllersIController;
    use Varhall\Restino\Controllers\Attributes;
    use Varhall\Restino\Filters\Cors;
    use Varhall\Restino\Filters\Chain;

    #[Path('/api/users')]
    class UsersController implements IController
    {
        public function __construct(private Chain $filters) {}

        public function setup(): void
        {
            $this->filters->add('cors', new Cors([
                'allow_origin'  => 'https://example.com',
                'allow_methods' => ['GET, 'POST', 'PUT', 'DELETE', 'OPTIONS'],
                'allow_headers' => ['Content-Type, 'Authorization', 'X-Api-Key'],
            ]));
        }
    }

<a name="secured-filter"></a>
## Secured filter

`Secured` filter is used to check if the user is authenticated. It uses standard Nette authentication
mechanism `Nette\Security\User` and its `isLoggedIn` method. If the user is not authenticated, the filter
returns `401 Unauthorized` HTTP code.

Attribute `#[Secured]` can be used of course on class or method level.

    <?php

    use App\Models\User;
    use VarhallRestinoControllersIController;
    use Varhall\Restino\Controllers\Attributes;
    use Varhall\Utilino\Collections\ICollection;

    #[Secured]
    #[Path('/api/users')]
    class UsersController implements IController
    {
        #[Get]
        public function index(): ICollection
        {
            return User::all();
        }
    }

<a name="role-filter"></a>
## Role filter

Role filter is used to check if the user has required role. It uses standard Nette authentication
mechanism `Nette\Security\User` and its `isInRole` method. If the user has not required role, the filter
returns `401 Unautorized` HTTP code.

It takes parameter `role` in constructor. Attribute `#[Role]` can be used of course on class or method level.

    use App\Models\User;
    use VarhallRestinoControllersIController;
    use Varhall\Restino\Controllers\Attributes;
    use Varhall\Utilino\Collections\ICollection;

    #[Role('admin')]
    #[Path('/api/users')]
    class UsersController implements IController
    {
        #[Get]
        public function index(): ICollection
        {
            return User::all();
        }
    }

<a name="closure-filter"></a>
## Closure filter

Closure filter is used to call custom function.

    <?php

    namespace App\Controllers;

    use VarhallRestinoControllersIController;
    use Varhall\Restino\Controllers\Attributes;
    use Varhall\Restino\Filters\Context;
    use Varhall\Restino\Filters\Chain;

    #[Path('/api/users')]
    class UsersController implements IController
    {
        public function __construct(private Chain $filters) {}

        public function setup(): void
        {
            $this->filters->add('my-filter', function (Context $context, callable $next) {
                // do something
            });
        }
    }

<a name="custom-filter"></a>
## Custom filter

You can define your own filter. The filter must implement `Varhall\Restino\Filters\IFilter` 
interface. The interface has only one method `execute` which takes `Varhall\Restino\Filters\Context`,
`callable` to call next filter and returns `Varhall\Restino\Results\IResult`.

    <?php

    namespace App\Controllers;

    use VarhallRestinoControllersIController;
    use Varhall\Restino\Controllers\Attributes;
    use Varhall\Restino\Results\IResult;
    use Varhall\Restino\Filters\IFilter;
    use Varhall\Restino\Filters\Chain;

    #[Path('/api/users')]
    class UsersController implements IController
    {
        public function __construct(private Chain $filters) {}

        public function setup(): void
        {
            $this->filters->add('my-filter', new class implements IFilter {
                public function execute(Context $context, callable $next): IResult
                {
                    // do something
                    return $next($context);
                }
            });
        }
    }

<a name="filter-parameters"></a>
### Filter parameters

In most cases filter needs to work with some extra parameters. The parameters can be passed to filter
through constructor. The parameters can be defined in `config.neon` file.

    restino:
        filters:
            my-filter: App\MyFilter(%myParameter%)

or in presenter class.

    <?php

    namespace App\Controllers;

    use VarhallRestinoControllersIController;
    use Varhall\Restino\Controllers\Attributes;
    use Varhall\Restino\Controllers\RestRequest;
    use Varhall\Restino\Filters\IFilter;
    use Varhall\Restino\Filters\Chain;

    #[Path('/api/users')]
    class UsersController implements IController
    {
        public function __construct(private Chain $filters) {}

        public function setup(): void
        {
            $this->filters->add('my-filter', new MyFilter('foo', 'bar'));
        }
    }

Filters can also be registered in standard Nette DI container as service. In this case the parameters
are passed automatically.

<a name="terminating-filter"></a>
### Terminating filter

Terminating filter is used to terminate the request. It is used for example for authentication filter
which terminates the request if the user is not authenticated. In case of early termination, the filter
should return `Termination` object. Example usage could be

    public function execute(Context $context, callable $next): IResult
    {
        if (!$this->user->isInRole($this->role)) {
            return new Termination([ 'message' => 'Operation is not allowed' ], IResponse::S401_Unauthorized);
        }

        return $next($request);
    }

<a name="request-and-response-filter"></a>
### Request and response filter

Request filter handles the request before it is processed by controller. Response filter handles the request
after it is processed by controller. The request filter is called in order they are registered. The response
filter is called in reverse order they are registered.

The request/response usage depends on when `$next` is called. If `$next` is called before the custom code, the after
filter is called before the custom code. If `$next` is called after the custom code, the after filter
is called after the custom code.

    public function execute(Context $context, callable $next): IResult
    {
        // request filter

        $result = $next($request);

        // response filter

        return $result;
    }

<a name="filter-attributes"></a>
## Filter attributes

In some case it is nice to activate filter with special attribute. For example to activate authentication
or allow only clients with specific role. Some of predefined filters are allowed to be used as attributes.
These attributes can be defined on class or method level, depending on the filter.

Definition on class level means that the filter is applied to all endpoints in the class.

    <?php

    namespace App\Controllers;

    use VarhallRestinoControllersIController;
    use Varhall\Restino\Controllers\Attributes;
    use Varhall\Restino\Controllers\RestRequest;
    use Varhall\Restino\Filters\IFilter;

    #[Secured]
    #[Path('/api/users')]
    class UsersController implements IController
    {
        #[Get]
        public function index(): mixed
        {
            // This endpoint is secured, only authenticated users can access it.
        }

        #[Get('{id}')]
        public function get(int $id): mixed
        {
          // This endpoint is secured, only authenticated users can access it.
        }
    }

Definition on method level means that the filter is applied only to the specific endpoint.

    <?php

    namespace App\Controllers;

    use VarhallRestinoControllersIController;
    use Varhall\Restino\Controllers\Attributes;
    use Varhall\Restino\Controllers\RestRequest;
    use Varhall\Restino\Filters\IFilter;

    #[Path('/api/users')]
    class UsersController implements IController
    {
        #[Secured]
        #[Get]
        public function index(): mixed
        {
            // This endpoint is secured, only authenticated users can access it.
        }

        #[Get('{id}')]
        public function get(int $id): mixed
        {
          // This endpoint is public
        }
    }

<a name="custom-filter-attributes"></a>
### Custom filter attributes

The implementation of custom filter attribute is very simple. The attribute must implement `Varhall\Restino\Filters\IFilter`
which defines `execute` method. If you want to use the filter as attribute, it must be annotated with `Attribute`.

    <?php

    namespace Varhall\Restino\Filters;

    use Varhall\Restino\Results\IResult;
    
    #[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
    class Custom implements IFilter
    {
        public function execute(Context $context, callable $next): IResult
        {
            // custom operation
    
            return $next($context);
        }
    }
