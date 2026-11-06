# Endpoints

- [Controller definition](#controller-definition)
  - [Controller DI registration](#controller-di)
- [Routing](#routing)
- [Schema generator](#schema-generator)
  - [Endpoint versions](#endpoint-versions)
- [Input parameters](#input-parameters)
  - [Simple parameters](#parameters-simple)
  - [Complex parameters](#parameters-complex)
  - [Nested parameters](#parameters-nested)
  - [Validation rules](#parameters-validation-rules)
  - [Validating method parameters](#parameters-validation-methods)
  - [Validation errors](#parameters-validation-errors)
  - [Raw request](#parameters-raw-request)
- [Setup](#setup)


<a name="controller-definition"></a>
## Controller definition

Controller is the key unit of Restino. It implements `Varhall\Restino\Controllers\IController` interface.
Controller consists of public methods which are mapped to HTTP methods and URL paths. These methods are called
endpoints or actions. The mapping is defined by PHP attributes and turned into a `Schema` by `SchemaGenerator`.

Basic definition of controller can look like:

    <?php

    namespace App\Controllers;

    use App\Models\User;
    use Varhall\Utilino\Collections\ICollection;
    use Varhall\Restino\Controllers\IController;
    use Varhall\Restino\Controllers\Attributes\Path;
    use Varhall\Restino\Controllers\Attributes\Get;
    use Varhall\Restino\Controllers\Attributes\Post;
    use Varhall\Restino\Controllers\Attributes\Put;
    use Varhall\Restino\Controllers\Attributes\Delete;

    #[Path('/api/users')]
    class UsersController implements IController
    {
        #[Get]
        public function index(): ICollection
        {
            return User::all();
        }

        #[Get('/{id}')]
        public function get(int $id): User
        {
            return User::find($id);
        }

        #[Post]
        public function create(UserInput $data): User
        {
            return User::create((array) $data);
        }

        #[Put('/{id}')]
        public function update(int $id, UserInput $data): User
        {
            return User::find($id)->update((array) $data);
        }

        #[Delete('/{id}')]
        public function delete(int $id): void
        {
            User::find($id)->delete();
        }
    }

What an action can return and how it is sent to the client is described in [Responses](results.md).

<a name="controller-di"></a>
### Controller DI registration

Controllers must be registered in the Nette DI container. The controller can be registered as a service manually
or using automatic registration in `config.neon`:

    search:
        -
            in: %appDir%
            classes:
                - *Controller

    decorator:
        Varhall\Restino\Controllers\IController:
            inject: true

This causes all classes ending with `Controller` to be registered as services and `inject*` methods of controllers
to be called. It is very useful for sharing dependencies through traits.

<a name="routing"></a>
## Routing

As in standard Nette application, the routing is usually defined in `RouterFactory`. The `ApiRouter`
maps HTTP requests to controllers. The `ApiRouter` requires `Schema` object which can be generated using
`SchemaGenerator` automatically. If automatic scanning is not desired, the `Schema` can be defined manually
or loaded from file.

The recommended way is `ApiRouterFactory` service. It generates the schema from controller attributes and, when
[versioning](versioning.md) is enabled, creates the router which selects endpoints by client version.

    <?php

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

The router can also be created directly from the schema (versioning is not applied in this case):

    <?php

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

Because `RouterFactory` has dependencies, the `create` method cannot be static. Service registration
in `config.neon` can look like this:

    services:
        - App\Router\RouterFactory
        - @App\Router\RouterFactory::create

`ApiRouter` only matches requests (it does not generate URLs) and can be combined with standard Nette routes in
one `RouteList` - for example an API and a web application in one project.

<a name="schema-generator"></a>
## Schema generator

`SchemaGenerator` scans registered controllers and generates the schema from PHP attributes. Controllers without
`#[Path]` attribute are skipped. The `#[Path]` attribute defines the base path of all endpoints of the controller.

Endpoints are defined using attributes `#[Get]`, `#[Post]`, `#[Put]` and `#[Delete]`. These attributes map the action
method to the HTTP method and URL path. The path is relative to the controller path; when it is not defined, the
endpoint is on the controller path itself. Any other HTTP method can be used with the generic attribute
`#[Action('PATCH', '/{id}')]`.

Path definition can contain parameters in curly braces, e.g. `{id}`. A parameter matches one path segment of
letters, digits, `_` and `-`. Its value is passed to the action parameter of the same name.

    <?php

    namespace App\Controllers;

    use App\Models\User;
    use Varhall\Restino\Controllers\IController;
    use Varhall\Restino\Controllers\Attributes\Path;
    use Varhall\Restino\Controllers\Attributes\Post;

    #[Path('/api/users')]
    class UsersController implements IController
    {
        #[Post('/{id}/activate')]          // POST /api/users/15/activate
        public function activate(int $id): User
        {
            return User::find($id)->update(['enabled' => true]);
        }
    }

The router takes the first endpoint whose method and path match the request. Declare actions with fixed paths
(e.g. `/info`) before actions with parameters (e.g. `/{id}`) in the same controller.

Each combination of HTTP method and path can be defined only once - `SchemaGenerator` throws
`Nette\InvalidStateException` (*Duplicate endpoint detected*) otherwise. The only exception are endpoints with
non-overlapping version ranges.

<a name="endpoint-versions"></a>
### Endpoint versions

With [versioning](versioning.md) enabled, an endpoint can be limited to a range of client versions using
`since` (version `>= since`) and `before` (version `< before`) arguments of `#[Get]`, `#[Post]`, `#[Put]` and
`#[Delete]`. Two actions can then share the same method and path:

    #[Post('/{id}/verify', before: 5)]
    public function verifyLegacy(string $id): InvitationResponse { /* clients 1 - 4 */ }

    #[Post('/{id}/verify', since: 5)]
    public function verify(string $id, VerifyRequest $data): InvitationResponse { /* clients 5+ */ }

See [version-based routing](versioning.md#routing) for details.

<a name="input-parameters"></a>
## Input parameters

Action parameters are filled automatically from the request. Path parameters, query parameters and the JSON body
are merged into one set of values (in this order, later sources override earlier ones). Every value is converted
to the declared type and validated; invalid input ends with a [validation error](#parameters-validation-errors) and
the action is not called.

| Parameter type | Value |
| --- | --- |
| scalar (`string`, `int`, `float`, `bool`) | the request value of the same name, converted to the type |
| `array`, `mixed` | the request value of the same name, unchanged |
| `DateTime` | the request value of the same name parsed as a date (`2024-01-31`, `2024-01-31T10:00:00Z`, timestamp) |
| class | **all** request values mapped to the properties of the class |
| `RestRequest` | the [raw request](#parameters-raw-request) |

A nullable parameter (`?int $page`) accepts a missing value; the strings `null` and `nil` are converted to `null`.
A non-nullable parameter is mandatory.

> Default values of action parameters (`int $limit = 10`) are **not** used - a missing value is passed as `null`
> and fails validation of a non-nullable type. Declare optional parameters as nullable and apply the default in the
> action: `?int $limit` ... `$limit ??= 10`.

<a name="parameters-simple"></a>
### Simple parameters

    <?php

    namespace App\Controllers;

    use App\Models\User;
    use Varhall\Restino\Controllers\IController;
    use Varhall\Utilino\Collections\ICollection;
    use Varhall\Restino\Controllers\Attributes\Path;
    use Varhall\Restino\Controllers\Attributes\Get;

    #[Path('/api/users')]
    class UsersController implements IController
    {
        #[Get]
        public function index(string $type, bool $enabled, ?int $limit): ICollection
        {
            return User::all()->where('type', $type)->where('enabled', $enabled);
        }
    }

The request URL for this case would be `GET /api/users?type=customer&enabled=true`. Query values are strings,
they are converted to the declared types: `enabled=true` (also `1`, `on`, `yes`) becomes `true`, numbers become
`int` / `float`.

A parameter of type `array` gets the value of the same name, not the whole body - `create(array $data)` expects
a body like `{ "data": { ... } }`. To map the whole body, use an input class.

<a name="parameters-complex"></a>
### Complex parameters

A parameter with a class type receives all request values mapped to the public properties of the class (the
parameter name does not matter). Properties are converted according to their types, unknown values are ignored.

    <?php

    namespace App\Controllers;

    use App\Models\User;
    use Varhall\Restino\Controllers\IController;
    use Varhall\Restino\Controllers\Attributes\Path;
    use Varhall\Restino\Controllers\Attributes\Post;

    #[Path('/api/users')]
    class UsersController implements IController
    {
        #[Post]
        public function create(UserInput $data): User
        {
            // $data->name, $data->surname, $data->enabled
        }
    }

Class `UserInput` can look like this:

    <?php

    namespace App\Controllers;

    class UserInput
    {
        public string $name;
        public string $surname;
        public bool $enabled;
    }

The request for this case could be `POST /api/users` with body:

    {
        "name": "John",
        "surname": "Doe",
        "enabled": true
    }

Values which cannot be converted to the property type are reported as validation errors.

**Missing values.** Properties are optional by default - a property missing in the request is left uninitialized
(a typed property without a value throws `Error` when read). Mark mandatory properties with `#[Required]` and give
optional ones a default value:

    use Varhall\Utilino\Mapping\Attributes\Required;

    class UserInput
    {
        #[Required]
        public string $name;

        #[Required]
        public string $surname;

        public bool $enabled = true;
    }

<a name="parameters-nested"></a>
### Nested parameters

The input class can contain properties of other classes. Nested objects are mapped the same way.

    <?php

    namespace App\Controllers;

    class UserInput
    {
        public string $name;
        public string $surname;
        public bool $enabled;
        public AddressInput $address;
    }

    class AddressInput
    {
        public string $street;
        public string $city;
        public string $zip;
    }

The request for this case could be `POST /api/users` with body:

    {
        "name": "John",
        "surname": "Doe",
        "enabled": true,
        "address": {
            "street": "Main Street",
            "city": "New York",
            "zip": "12345"
        }
    }

<a name="parameters-validation-rules"></a>
### Validation rules

The input is validated according to the declared types. More specific requirements are defined with attributes from
`Varhall\Utilino\Mapping\Attributes`:

| Attribute | Description |
| --- | --- |
| `#[Rule('string:3..')]` | Expected type in [Nette Validators syntax](https://doc.nette.org/en/utils/validators#toc-expected-types) - ranges (`int:1..10`, `string:3..`), `email`, `url`, `numericint`, unions (`int\|string`) and more. Replaces the type check of the property. |
| `#[Enum('draft', 'published')]` | Value must be one of the listed values. |
| `#[Required]` | Value must be present (properties only). |
| `#[Implicit(10)]` | Default value when the value is missing (properties only; a PHP default value of the property works the same way). |
| `#[Assert(callable, 'message')]` | Custom check, the callable gets the value and returns `bool`. |
| `#[Date]` | Value is parsed as a date (applied automatically for `DateTime` types). |

    <?php

    namespace App\Controllers;

    use Varhall\Utilino\Mapping\Attributes\Assert;
    use Varhall\Utilino\Mapping\Attributes\Enum;
    use Varhall\Utilino\Mapping\Attributes\Required;
    use Varhall\Utilino\Mapping\Attributes\Rule;

    class UserInput
    {
        #[Required]
        #[Rule('string:3..')]
        public string $name;

        #[Required]
        #[Rule('string:1..')]
        public string $surname;

        #[Required]
        #[Rule('email')]
        public string $email;

        #[Rule('int:0..150')]
        public ?int $age = null;

        #[Enum('customer', 'partner')]
        public string $type = 'customer';

        #[Assert([ PhoneNumber::class, 'isValid' ], 'Invalid phone number')]
        public ?string $phone = null;
    }

Only one of `#[Rule]`, `#[Enum]` and `#[Date]` can be used on one property.

<a name="parameters-validation-methods"></a>
### Validating method parameters

Action parameters are validated the same way as properties of input classes and accept `#[Rule]`, `#[Enum]`,
`#[Date]` and `#[Assert]`. Mandatory parameters have a non-nullable type, optional parameters a nullable type
(see [defaults](#input-parameters)).

    <?php

    namespace App\Controllers;

    use App\Models\User;
    use Varhall\Restino\Controllers\Attributes\Get;
    use Varhall\Restino\Controllers\Attributes\Path;
    use Varhall\Restino\Controllers\IController;
    use Varhall\Utilino\Collections\ICollection;
    use Varhall\Utilino\Mapping\Attributes\Rule;

    #[Path('/api/users')]
    class UsersController implements IController
    {
        #[Get]
        public function index(#[Rule('string:3..')] string $type): ICollection
        {
            return User::all()->where('type', $type);
        }
    }

<a name="parameters-validation-errors"></a>
### Validation errors

When any parameter is invalid, the action is not called and the client receives `400 Bad Request` with all errors.
Keys are paths to the invalid values - the parameter name, followed by property names for input classes:

    POST /api/users
    { "name": "Jo", "email": "nope" }

    HTTP/1.1 400 Bad Request

    {
        "data.name": "The item 'data › name' expects to be string in range 3.., 'Jo' given.",
        "data.email": "The item 'data › email' expects to be email, 'nope' given."
    }

<a name="parameters-raw-request"></a>
### Raw request

A parameter of type `Varhall\Restino\Controllers\RestRequest` receives the request object with all merged
parameters (`getParameters()`, `getParameter($name, $default)`) and the Nette application request
(`getRequest()`). For HTTP headers inject `Nette\Http\IRequest` into the controller.

    #[Post('/import')]
    public function import(RestRequest $request): array
    {
        $rows = $request->getParameter('rows', []);
        // ...
    }

<a name="setup"></a>
## Setup

Controller can have a method `setup` which is called before every action of the controller (after the filters from
attributes are registered and before the filter chain runs). Typical usage is to register or configure filters, see
[Filters](filters.md#filter-configuration).

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
            $this->filters->get('authentication')->except('create');
        }
    }
