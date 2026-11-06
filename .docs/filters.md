# Filters

- [Introduction](#introduction)
- [Registering filters](#registering-filters)
    - [Global filters](#global-filters)
    - [Endpoint filters](#endpoint-filters)
    - [Filter configuration](#filter-configuration)
    - [Filter order](#filter-order)
- [Collection filter](#collection-filter)
- [Filter filter](#filter-filter)
- [Expand filter](#expand-filter)
- [Map filter](#map-filter)
- [CORS filter](#cors-filter)
    - [CORS header providers](#cors-header-providers)
- [Secured filter](#secured-filter)
- [Role filter](#role-filter)
- [Closure filter](#closure-filter)
- [Custom filter](#custom-filter)
    - [Filter parameters](#filter-parameters)
    - [Terminating filter](#terminating-filter)
    - [Request and response filter](#request-and-response-filter)
    - [Filter context](#filter-context)
- [Filter attributes](#filter-attributes)
    - [Custom filter attributes](#custom-filter-attributes)

<a name="introduction"></a>
## Introduction

Filters are middlewares wrapped around the controller action. Each filter gets the request, may stop it (for
example when the user is not authenticated) or pass it to the next filter, and gets the result back, so it can
modify the response as well.

    request ──► Cors ──► Secured ──► Collection ──► action
    response ◄── Cors ◄── Secured ◄── Collection ◄──┘

Filters included in Restino:

| Filter | Attribute | Purpose |
| --- | --- | --- |
| [`Collection`](#collection-filter) | `#[Collection]` | Pagination and ordering of collections controlled by the client (`_limit`, `_offset`, `_order`). |
| [`Filter`](#filter-filter) | `#[Filter]` | Filtering of database collections by query parameters (`?age>=30`). |
| [`Expand`](#expand-filter) | `#[Expand]` | Adds related data requested by the client (`_expand=address`). |
| [`Map`](#map-filter) | `#[Map(Class::class)]` | Wraps every returned item into a response class. |
| [`Cors`](#cors-filter) | - | CORS headers and preflight requests. |
| [`Secured`](#secured-filter) | `#[Secured]` | Only authenticated users. |
| [`Role`](#role-filter) | `#[Role('admin')]` | Only users in a role. |
| [`Closure`](#closure-filter) | - | Anonymous function as a filter. |

With [versioning](versioning.md) enabled, Restino registers its own filter named `versioning` as well.

<a name="registering-filters"></a>
## Registering filters

<a name="global-filters"></a>
### Global filters

Globally registered filters are applied to all endpoints. They are registered in `config.neon`; the key is the
name of the filter, the value is a class name or a reference to a service.

    restino:
        filters:
            cors: Varhall\Restino\Filters\Cors
            collection: Varhall\Restino\Filters\Collection
            filter: Varhall\Restino\Filters\Filter
            authentication: @App\Api\AuthenticationFilter

A class name is instantiated without constructor arguments. A filter which needs arguments or services must be
registered as a service and referenced with `@` - see [filter parameters](#filter-parameters).

<a name="endpoint-filters"></a>
### Endpoint filters

Endpoint filters are applied only to specific actions. They are attached using PHP attributes on the action or on
the controller class (then they apply to all actions of the controller):

    <?php

    namespace App\Controllers;

    use App\Models\User;
    use Varhall\Restino\Controllers\Attributes\Get;
    use Varhall\Restino\Controllers\Attributes\Path;
    use Varhall\Restino\Controllers\IController;
    use Varhall\Restino\Filters\Collection;
    use Varhall\Restino\Filters\Secured;
    use Varhall\Utilino\Collections\ICollection;

    #[Secured]
    #[Path('/api/users')]
    class UsersController implements IController
    {
        #[Get]
        #[Collection]
        public function index(): ICollection
        {
            return User::all();
        }
    }

Filters can also be added in the `setup()` method of the controller, which is called before every action of the
controller:

    <?php

    namespace App\Controllers;

    use Varhall\Restino\Controllers\Attributes\Path;
    use Varhall\Restino\Controllers\IController;
    use Varhall\Restino\Filters\Chain;
    use Varhall\Restino\Filters\Cors;

    #[Path('/api/users')]
    class UsersController implements IController
    {
        public function __construct(private Chain $filters) {}

        public function setup(): void
        {
            $this->filters->add('cors', new Cors([ 'allow_origin' => 'https://example.com' ]));
        }
    }

`Chain` is a shared service. Filters added in `setup()` stay registered for the rest of the PHP process, so in
long-running processes (workers) use `$this->filters->has('cors')` before adding. Filters created from attributes
are removed automatically after each request.

<a name="filter-configuration"></a>
### Filter configuration

Globally registered filters sometimes need to be adjusted for a controller. A typical case is an authentication
filter registered globally, which must be disabled for some actions. `Chain::get($name)` returns the configuration
of the filter with methods `only` and `except`. Both take a name of the action method or a list of them.

    <?php

    namespace App\Controllers;

    use Varhall\Restino\Controllers\Attributes\Path;
    use Varhall\Restino\Controllers\IController;
    use Varhall\Restino\Filters\Chain;

    #[Path('/api/users')]
    class UsersController implements IController
    {
        public function __construct(private Chain $filters) {}

        public function setup(): void
        {
            // authentication runs only for actions index and detail
            $this->filters->get('authentication')->only(['index', 'detail']);

            // or: authentication runs for all actions except register
            // $this->filters->get('authentication')->except('register');
        }
    }

`reset()` removes all conditions. When `only` and `except` are combined, the filter runs for the actions listed
in `only` and for all actions not listed in `except`.

The filter can be completely removed from the chain using `remove` method:

    $this->filters->remove('authentication');

<a name="filter-order"></a>
### Filter order

Filters run in the order of registration - the first registered filter is the outermost one. It sees the request
first and the result last. Filters from `config.neon` are registered first, then filters from attributes (class
level, then method level) and finally filters added in `setup()`.

When a filter must run at a specific place regardless of the registration order, use `after()` and `before()`:

- `after(...$references)` - the filter runs after (inside) the referenced filters,
- `before(...$references)` - the filter runs before (outside) the referenced filters.

A reference is a filter name or a class / interface name. A class reference matches every registered filter which is
an instance of it, under any name. Missing references are ignored, so a filter can declare its position relative
to filters which may not be registered at all.

    <?php

    namespace App\Controllers;

    use Varhall\Restino\Controllers\Attributes\Path;
    use Varhall\Restino\Controllers\IController;
    use Varhall\Restino\Filters\Chain;
    use Varhall\Restino\Filters\Cors;

    #[Path('/api/users')]
    class UsersController implements IController
    {
        public function __construct(private Chain $filters) {}

        public function setup(): void
        {
            // runs inside every Cors filter and inside the filter named "requestlog"
            $this->filters->add('audit', new AuditFilter())->after(Cors::class, 'requestlog');

            // runs outside the filter named "authentication"
            $this->filters->add('timing', new TimingFilter())->before('authentication');
        }
    }

The order is resolved when the chain is built for a request, so constraints work with filters registered later as
well. Filters without constraints keep their registration order, a constrained filter is placed right behind the
last (or in front of the first) referenced filter. `Chain::getOrderedFilters()` returns the resolved order;
contradicting constraints throw `Nette\InvalidStateException`.

The [versioning filter](versioning.md#filter-order) uses this mechanism to run inside `Cors`.

<a name="collection-filter"></a>
## Collection filter

An action returning `Varhall\Utilino\Collections\ICollection` (e.g. a Dbino collection) or
`Nette\Database\Table\Selection` produces a collection result. Collection results are always paginated - by default
the first 100 items are returned, the maximum page size is 1000 (both [configurable](results.md#collection-limits)).
Pagination information is sent in response headers:

| Header | Description |
| --- | --- |
| `X-Limit` | Page size. |
| `X-Offset` | Offset of the first returned item. |
| `X-Total` | Total number of items. |
| `X-Next-Offset` | Offset of the next page, empty on the last page. |
| `X-Previous-Offset` | Offset of the previous page, empty on the first page. |

The `Collection` filter lets the client control the pagination and ordering with query parameters:

| Parameter | Description |
| --- | --- |
| `_limit` | Page size, capped by the configured maximum (1000 by default). |
| `_offset` | Number of items to skip. |
| `_order` | Column to order by. Prefix `-` means descending order, more columns are separated by comma (`_order=name,-age`). Ordering is applied to Dbino collections and Nette Database selections. |

    <?php

    namespace App\Controllers;

    use App\Models\User;
    use Varhall\Restino\Controllers\Attributes\Get;
    use Varhall\Restino\Controllers\Attributes\Path;
    use Varhall\Restino\Controllers\IController;
    use Varhall\Restino\Filters\Collection;
    use Varhall\Utilino\Collections\ICollection;

    #[Path('/api/users')]
    class UsersController implements IController
    {
        #[Get]
        #[Collection]
        public function index(): ICollection
        {
            return User::all();
        }
    }

    GET /api/users?_limit=3&_offset=9&_order=name

    HTTP/1.1 200 OK
    X-Limit: 3
    X-Offset: 9
    X-Total: 45
    X-Next-Offset: 12
    X-Previous-Offset: 6

    [
        { "id": 12, "name": "Lars", "surname": "Ulrich" },
        { "id": 7, "name": "Ludwig", "surname": "König" },
        { "id": 31, "name": "Sebastian", "surname": "Winkler" }
    ]

Browser clients can read the pagination headers only if they are listed in `Access-Control-Expose-Headers` - see
the [CORS filter](#cors-filter).

<a name="filter-filter"></a>
## Filter filter

`Filter` filters a collection result by query parameters. It works with `Varhall\Dbino\Collections\Collection`
and `Nette\Database\Table\Selection` results; every query parameter becomes a `WHERE` condition on the column of
the same name.

    GET /api/users?name=Sebastian&age>=30

returns users with name Sebastian and age greater or equal to 30.

| Operator | Description              | Example                          |
|----------|--------------------------|----------------------------------|
| `=`      | Equal to                 | `/api/users?name=Sebastian`      |
| `!=`     | Not equal to             | `/api/users?name!=Sebastian`     |
| `>`      | Greater than             | `/api/users?age>30`              |
| `>=`     | Greater than or equal to | `/api/users?age>=30`             |
| `<`      | Less than                | `/api/users?age<30`              |
| `<=`     | Less than or equal to    | `/api/users?age<=30`             |
| `*`      | LIKE                     | `/api/users?name=Seb*`           |
| `,`      | IN (`!=` = NOT IN)       | `/api/users?name=Sebastian,Lars` |
| `null`   | IS NULL (`!=null` = IS NOT NULL) | `/api/users?deleted_at=null` |

A date value (`YYYY-MM-DD`) with `=` matches the whole day (`created=2024-01-31`). Parameters `_limit`, `_offset`,
`_order` and `_expand` are ignored.

> **Security:** the filter does not restrict columns. Every query parameter is used as a condition, including
> parameters of the action itself and columns the client should not see (e.g. filtering by `password_hash=a*`
> reveals information about its value). An unknown column causes a database error. Use the filter only on
> endpoints returning non-sensitive tables, or restrict the collection before returning it (e.g. select only
> public columns).

<a name="expand-filter"></a>
## Expand filter

`Expand` adds related data to the response on request. The client lists the requested relations in the `_expand`
query parameter, the returned object defines which relations are available by implementing
`Varhall\Restino\Results\IExpandable`:

    <?php

    namespace App\Api\Responses;

    use App\Models\User;
    use Varhall\Restino\Results\IExpandable;
    use Varhall\Utilino\ISerializable;

    class UserResponse implements ISerializable, IExpandable
    {
        public function __construct(private User $user) {}

        public function toArray(): array
        {
            return [
                'id'        => $this->user->id,
                'name'      => $this->user->name,
                'surname'   => $this->user->surname,
            ];
        }

        public function expansions(): array
        {
            return [
                // callable - receives this object
                'full_name' => fn(self $response) => "{$this->user->name} {$this->user->surname}",

                // string - name of the method of this object
                'role'      => 'role',

                // list item - expanded property and method have the same name
                'address',
            ];
        }

        public function role(): RoleResponse
        {
            return new RoleResponse($this->user->role);
        }

        public function address(): AddressResponse
        {
            return new AddressResponse($this->user->address);
        }
    }

Enable the filter globally (`expand: Varhall\Restino\Filters\Expand` in `restino.filters`) or with the attribute:

    #[Get('/{id}')]
    #[Expand]
    public function detail(int $id): UserResponse
    {
        return new UserResponse(User::find($id));
    }

    GET /api/users/1?_expand=full_name,role,address

    {
        "id": 1,
        "name": "Dave",
        "surname": "Murray",
        "full_name": "Dave Murray",
        "role": { "id": 1, "name": "Guitar" },
        "address": { "id": 1, "street": "Main Street", "city": "London", "zip": "E1 7HX" }
    }

Relations which are not requested are not evaluated. Unknown names in `_expand` are ignored. For lists and
collections, every item is expanded. Items which do not implement `IExpandable` are returned unchanged.

When the action returns models and the response class is created by the [Map filter](#map-filter), `Expand` must
run outside of `Map` - register `Expand` globally, or declare `#[Expand]` above `#[Map]`.

Expanded values are serialized by the serializer of the result, so with [versioning](versioning.md#downgrade-collections)
enabled the expanded relations are converted to the client version as well. If you call `Expand::map()` in your own
mapper, pass the serializer of the result as the third argument (`$result->getSerializer()`).

<a name="map-filter"></a>
## Map filter

`Map` wraps every returned item into the given class - `new UserResponse($item)`. It is useful when the action
returns models or a collection and the response shape is defined by a response class.

    #[Get]
    #[Expand]
    #[Map(UserResponse::class)]
    public function index(): ICollection
    {
        return User::all();     // each user is returned as new UserResponse($user)
    }

For lists and collections the mapping is applied to each item (of the current page), for any other result to the
returned value itself. `Map` is applied before mappers of filters declared above it (e.g. `#[Expand]`).

<a name="cors-filter"></a>
## CORS filter

`Cors` filter returns CORS headers. The default values are:

    Access-Control-Allow-Origin: *
    Access-Control-Allow-Methods: GET, POST, PUT, DELETE
    Access-Control-Allow-Headers: Content-type, Authorization

The default values can be changed using options array in constructor.

    <?php

    namespace App\Controllers;

    use Varhall\Restino\Controllers\Attributes\Path;
    use Varhall\Restino\Controllers\IController;
    use Varhall\Restino\Filters\Chain;
    use Varhall\Restino\Filters\Cors;

    #[Path('/api/users')]
    class UsersController implements IController
    {
        public function __construct(private Chain $filters) {}

        public function setup(): void
        {
            $this->filters->add('cors', new Cors([
                'allow_origin'  => 'https://example.com',
                'allow_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
                'allow_headers' => ['Content-Type', 'Authorization', 'X-Api-Key'],
                'expose_headers' => ['X-Total', 'X-Limit', 'X-Offset', 'X-Next-Offset', 'X-Previous-Offset'],
            ]));
        }
    }

Globally the filter with options is registered as a service:

    services:
        cors: Varhall\Restino\Filters\Cors([ allow_origin: 'https://example.com', expose_headers: [ X-Total ] ])

    restino:
        filters:
            cors: @cors

Available options:

| Option | Default | Header |
| --- | --- | --- |
| `allow_origin` | `*` | `Access-Control-Allow-Origin` |
| `allow_methods` | `GET, POST, PUT, DELETE` | `Access-Control-Allow-Methods` |
| `allow_headers` | `Content-type, Authorization` | `Access-Control-Allow-Headers` |
| `expose_headers` | none | `Access-Control-Expose-Headers` (response headers readable by browser scripts, sent only when not empty) |

For `OPTIONS` requests (preflight) the filter returns `200` immediately, other requests continue to the next filter.

**Preflight routing.** `ApiRouter` matches the HTTP method exactly, so an `OPTIONS` request reaches the filters only
when an endpoint for `OPTIONS` exists on that path. Either answer preflight requests before the application (web
server, `www/index.php`) - the most common setup - or declare `OPTIONS` endpoints using the generic `Action`
attribute:

    use Varhall\Restino\Controllers\Attributes\Action;

    #[Action('OPTIONS', '/{id}')]
    public function preflight(): void {}     // the Cors filter answers before the action is called

<a name="cors-header-providers"></a>
### CORS header providers

Extensions and libraries which introduce their own headers can add them to every `Cors` filter without changing its
configuration. Register a service implementing `Varhall\Restino\Filters\CorsHeaderProvider`:

    <?php

    namespace App\Api;

    use Varhall\Restino\Filters\CorsHeaderProvider;

    class TenantCorsHeaders implements CorsHeaderProvider
    {
        public function allowHeaders(): array
        {
            return [ 'X-Tenant' ];          // request header sent by the client
        }

        public function exposeHeaders(): array
        {
            return [ 'X-Tenant-Quota' ];    // response header read by the client
        }
    }

    services:
        - App\Api\TenantCorsHeaders

Headers of all providers are appended to the filter options (duplicates are ignored, case-insensitively).
[Versioning](versioning.md#filter-order) registers such a provider for the version header and advertised headers.

<a name="secured-filter"></a>
## Secured filter

`Secured` filter checks that the user is authenticated. It uses the standard Nette authentication
(`Nette\Security\User::isLoggedIn()`). An unauthenticated request is terminated:

    HTTP/1.1 401 Unauthorized

    { "message": "User is not authenticated" }

Attribute `#[Secured]` can be used on class or method level.

    <?php

    namespace App\Controllers;

    use App\Models\User;
    use Varhall\Restino\Controllers\Attributes\Get;
    use Varhall\Restino\Controllers\Attributes\Path;
    use Varhall\Restino\Controllers\IController;
    use Varhall\Restino\Filters\Secured;
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

How the user is authenticated (session, token in a header, ...) is up to the application - typically a custom
filter registered before `Secured` reads the token and calls `$user->login()`.

<a name="role-filter"></a>
## Role filter

`Role` filter checks that the user has the given role (`Nette\Security\User::isInRole()`). Otherwise the request is
terminated:

    HTTP/1.1 401 Unauthorized

    { "message": "Operation is not allowed" }

The role is passed in the constructor. Attribute `#[Role]` can be used on class or method level.

    <?php

    namespace App\Controllers;

    use App\Models\User;
    use Varhall\Restino\Controllers\Attributes\Get;
    use Varhall\Restino\Controllers\Attributes\Path;
    use Varhall\Restino\Controllers\IController;
    use Varhall\Restino\Filters\Role;
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

Any callable can be registered as a filter, it is wrapped into `Closure` filter. The callable has the same
signature as `IFilter::execute()` and must return a result.

    <?php

    namespace App\Controllers;

    use Varhall\Restino\Controllers\Attributes\Path;
    use Varhall\Restino\Controllers\IController;
    use Varhall\Restino\Filters\Chain;
    use Varhall\Restino\Filters\Context;
    use Varhall\Restino\Results\IResult;

    #[Path('/api/users')]
    class UsersController implements IController
    {
        public function __construct(private Chain $filters) {}

        public function setup(): void
        {
            $this->filters->add('timing', function (Context $context, callable $next): IResult {
                $start = microtime(true);
                $result = $next($context);

                $context->getHttpResponse()->setHeader('X-Duration', (string) round(microtime(true) - $start, 3));

                return $result;
            });
        }
    }

<a name="custom-filter"></a>
## Custom filter

A custom filter implements `Varhall\Restino\Filters\IFilter`. The interface has only one method `execute` which
takes `Varhall\Restino\Filters\Context`, `callable` to call the next filter and returns
`Varhall\Restino\Results\IResult`.

    <?php

    namespace App\Api\Filters;

    use Varhall\Restino\Filters\Context;
    use Varhall\Restino\Filters\IFilter;
    use Varhall\Restino\Results\IResult;

    class RequestIdFilter implements IFilter
    {
        public function execute(Context $context, callable $next): IResult
        {
            $id = $context->getHttpRequest()->getHeader('X-Request-Id') ?? bin2hex(random_bytes(8));

            $result = $next($context);
            $context->getHttpResponse()->setHeader('X-Request-Id', $id);

            return $result;
        }
    }

    restino:
        filters:
            requestid: App\Api\Filters\RequestIdFilter

<a name="filter-parameters"></a>
### Filter parameters

A filter which needs parameters or services receives them in the constructor. Register it as a service and
reference the service in `restino.filters`:

    services:
        authentication: App\Api\Filters\TokenAuthenticationFilter(%apiKeyHeader%)

    restino:
        filters:
            authentication: @authentication

Services in the constructor are autowired as usual. (A class name with arguments directly in `restino.filters`,
e.g. `App\MyFilter(%param%)`, is not supported - the item must be a class name or a service reference.)

In a controller the instance is created directly:

    public function setup(): void
    {
        $this->filters->add('my-filter', new MyFilter('foo', 'bar'));
    }

<a name="terminating-filter"></a>
### Terminating filter

A filter can stop the request without calling the next filter - for example an authentication filter when the
user is not authenticated. It returns `Varhall\Restino\Results\Termination` with the response body and HTTP code
(`400` by default):

    <?php

    namespace App\Api\Filters;

    use Nette\Http\IResponse;
    use Nette\Security\User;
    use Varhall\Restino\Filters\Context;
    use Varhall\Restino\Filters\IFilter;
    use Varhall\Restino\Results\IResult;
    use Varhall\Restino\Results\Termination;

    class TokenAuthenticationFilter implements IFilter
    {
        public function __construct(private string $header, private User $user, private TokenAuthenticator $tokens) {}

        public function execute(Context $context, callable $next): IResult
        {
            $token = $context->getHttpRequest()->getHeader($this->header);

            if ($token === null || !$this->tokens->login($this->user, $token)) {
                return new Termination([ 'message' => 'Invalid token' ], IResponse::S401_Unauthorized);
            }

            return $next($context);
        }
    }

<a name="request-and-response-filter"></a>
### Request and response filter

Code before `$next($context)` processes the request, code after it processes the result. Request parts of the
filters run in the order of the chain, response parts in the reverse order.

    public function execute(Context $context, callable $next): IResult
    {
        // request part - e.g. modify request parameters
        $context->getRequest()->setParameter('locale', 'cs');

        $result = $next($context);

        // response part - e.g. add headers or a mapper
        $context->getHttpResponse()->setHeader('Cache-Control', 'no-store');

        return $result;
    }

To transform the returned data, add a mapper to the result. Mappers are applied to every returned item before
serialization (this is how `Map` and `Expand` work):

    $result = $next($context);

    if ($result instanceof \Varhall\Restino\Results\AbstractResult) {
        $result->addMapper(fn($item) => $item instanceof User ? new UserResponse($item) : $item);
    }

    return $result;

<a name="filter-context"></a>
### Filter context

`Varhall\Restino\Filters\Context` passed to the filter provides:

| Method | Description |
| --- | --- |
| `getRequest(): RestRequest` | Request parameters (path, query and body merged). Parameters can be modified with `setParameters()` / `setParameter()` before the action is called. |
| `getEndpoint(): Endpoint` | Matched endpoint - path, HTTP method, controller class, action name and version range. |
| `getHttpRequest(): IRequest` | Nette HTTP request. |
| `getHttpResponse(): IResponse` | Nette HTTP response, e.g. for setting headers. |
| `getContainer(): Container` | DI container. |

    public function execute(Context $context, callable $next): IResult
    {
        $endpoint = $context->getEndpoint();
        $context->getHttpResponse()->setHeader('X-Action', "{$endpoint->controller}::{$endpoint->action}");

        return $next($context);
    }

<a name="filter-attributes"></a>
## Filter attributes

Filters `Collection`, `Filter`, `Expand`, `Map`, `Secured` and `Role` can be used as attributes. An attribute on the
class applies to all actions of the controller, an attribute on the method only to that action.

    <?php

    namespace App\Controllers;

    use Varhall\Restino\Controllers\Attributes\Get;
    use Varhall\Restino\Controllers\Attributes\Path;
    use Varhall\Restino\Controllers\IController;
    use Varhall\Restino\Filters\Role;
    use Varhall\Restino\Filters\Secured;

    #[Secured]
    #[Path('/api/users')]
    class UsersController implements IController
    {
        #[Get]
        public function index(): array
        {
            // only authenticated users
        }

        #[Get('/{id}')]
        #[Role('admin')]
        public function detail(int $id): array
        {
            // only authenticated users in role admin
        }
    }

Attribute filters run inside the global filters, class attributes before method attributes, in the order of
declaration.

<a name="custom-filter-attributes"></a>
### Custom filter attributes

Any filter can be used as an attribute when its class is marked with PHP `#[\Attribute]`:

    <?php

    namespace App\Api\Filters;

    use Varhall\Restino\Filters\Context;
    use Varhall\Restino\Filters\IFilter;
    use Varhall\Restino\Results\IResult;

    #[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
    class RateLimit implements IFilter
    {
        public function __construct(private int $perMinute) {}

        public function execute(Context $context, callable $next): IResult
        {
            $limiter = $context->getContainer()->getByType(RateLimiter::class);

            // ...

            return $next($context);
        }
    }

    #[Post('/login')]
    #[RateLimit(perMinute: 5)]
    public function login(LoginInput $data): TokenResponse { /* ... */ }

Attribute instances are created by PHP, not by the DI container - the constructor gets only the attribute
arguments. Get services from `$context->getContainer()`.
