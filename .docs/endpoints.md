# Endpoints

- [Controller definition](#controller-definition)
- [Routing](#routing)
- [Schema generator](#schema-generator)
- [Input parameters](#input-parameters)
  - [Simple parameters](#parameters-simple)
  - [Complex parameters](#parameters-complex)
  - [Nested parameters](#parameters-nested)
  - [Validation rules](#parameters-validation-rules)
  - [Validating method parameters](#parameters-validation-methods)
- [Setup](#setup)


<a name="controller-definition"></a>
## Controller definition

Controller is key unit of Restino. It requires `Varhall\Restino\Controllers\IController` interface.
Controller consists of methods which are mapped to HTTP methods and URL paths. These methods are called 
endpoints or actions. Endpoints are mapped to HTTP methods using `Schema` which can be easily generated
using `SchemaGenerator` and PHP attributes.

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
        public function create(array $data): User
        {
            return User::create($data);
        }

        #[Put('/{id}')]
        public function update(int $id, array $data): User
        {
            return User::find($id)->update($data);
        }

        #[Delete('/{id}')]
        public function delete(int $id): void
        {
            User::find($id)->delete();
        }
    }

<a name="routing"></a>
## Routing

As in standard Nette application, the routing is usually defined in `RouterFactory`. The `ApiRouter` 
maps HTTP requests to controllers. The `ApiRouter` requires `Schema` object which can be generated using
`SchemaGenerator` automatically. If automatic scanning is not desired, the `Schema` can be defined manually
or loaded from file.

The example of possible `RouterFactory` can look like this:

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

<a name="schema-generator"></a>
## Schema generator

The easiest way to generate `Schema` object is to use `SchemaGenerator` which scans your controllers
and generates the schema based on PHP attributes. To define a schema group, `#[Path]` attribute is used.
This path is relative to the base path of the API and all endpoints in the controller will be relative to this path.

Endpoints are defined using attributes `#[Get]`, `#[Post]`, `#[Put]` and `#[Delete]`. These attributes map the action 
function to the HTTP method and URL path. If path is not defined in the attribute, it defaults to the controller path.

Path definition can contain parameters. Parameters are defined in curly braces e.g. `{id}`. These parameters
are automatically extracted from the URL and passed to the action method as parameters or processed as input parameters
through `MappingService`.

    <?php

    namespace App\Controllers;

    use App\Models\User;
    use Varhall\Restino\Controllers\IController;
    use Varhall\Utilino\Collections\ICollection;
    use Varhall\Restino\Controller\Attributes\Path;
    use Varhall\Restino\Controller\Attributes\Post;

    #[Path('/api/users')]
    class UsersController implements IController
    {
        #[Post('/<id>/activate')]
        public function activate(int $id): User
        {
            return User::find($id)->update(['enabled' => true]);
        }
    }

The path defined in attribute is relative to the controller path. The controller path is defined in `RestRoute` definition
inside RouterFactory.

<a name="input-parameters"></a>
## Input parameters

API endpoint methods can have input parameters. These parameters are automatically filled from request data.
There automatic mapping and validation of input parameters. The input parameters can be defined using. Values
are taken from request.

<a name="parameters-simple"></a>
### Simple parameters

    <?php

    namespace App\Controllers;

    use App\Models\User;
    use Varhall\Restino\Controllers\IController;
    use Varhall\Utilino\Collections\ICollection;
    use Varhall\Restino\Controller\Attributes\Path;
    use Varhall\Restino\Controller\Attributes\Get;

    #[Path('/api/users')]
    class UsersController implements IController
    {
        #[Get]
        public function index(string $type, bool $enabled): ICollection
        {
            return User::all()->where('type', $type)->where('enabled', $enabled);
        }
    }

The request URL for this case would be `GET /api/users?type=customer&enabled=true`.

<a name="parameters-complex"></a>
### Complex parameters

Data can be passed in JSON format in request body. The data are automatically decoded and mapped to class. Values
are taken from request properties

    <?php

    namespace App\Controllers;

    use App\Models\User;
    use Varhall\Restino\Controllers\IController;
    use Varhall\Utilino\Collections\ICollection;
    use Varhall\Restino\Controller\Attributes\PAth;
    use Varhall\Restino\Controller\Attributes\Post;

    #[Path('/api/users')]
    class UsersController implements IController
    {
          #[Post]
          public function create(UserInput $data): User
          {
              // the code    
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

According to the property data types the values are automatically converted to the right type. If the type is not
supported, information is passed to validation output result.

<a name="parameters-nested"></a>
### Nested parameters

The input parameter class can also have nested complex type. The data are automatically decoded and mapped to class. Values
are taken from request properties

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

The input parameters are validated according to their data types. But there are often some complex requirements.
The rules can be defined using PHP attribute `#[Rule]`.

    <?php

    namespace App\Controllers;

    use Varhall\Utilino\Mapping\Attributes\Rule;
    
    class UserInput
    {
        #[Rule('string:3..')]
        public string $name;
    
        #[Rule('string:1..')]
        public string $surname;
    
        #[Rule('email')]
        public string $email;
    
        #[Rule('int', required: false)]
        public $age;
    }

The rules are defined using [Nette Validation](https://doc.nette.org/en/3.1/validation).

<a name="parameters-validation-methods"></a>
### Validating method parameters

Method parameters are validated same way as complex input parameters. The rules can be defined using PHP attribute `#[Rule]`.

    <?php

    namespace App\Controllers;

    use Varhall\Utilino\Mapping\Attributes\Rule;
    use Varhall\Restino\Controller\Attributes\Path;

    #[Path('/api/users')]
    class UsersController implements IController
    {
        #[Get]
        public function index(#[Rule('string:3..')] string $type): ICollection
        {
            return User::all()->where('type', $type)->where('enabled', $enabled);
        }
    }

<a name="setup"></a>
## Setup

Controller has special method `setup` which is called before each endpoint method. It can be used to configure
the controller. Typical usage is to register or configure filters.

    <?php

    namespace App\Controllers;

    use App\Models\User;
    use Varhall\Restino\Controllers\IController;
    use Varhall\Utilino\Collections\ICollection;
    use Varhall\Restino\Controller\Attributes\Post;
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

