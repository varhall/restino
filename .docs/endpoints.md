# Endpoints

- [Controller definition](#controller-definition)
- [Endpoint methods](#endpoint-methods)
    - [Index](#method-index)
    - [Get](#method-get)
    - [Create](#method-create)
    - [Update](#method-update)
    - [Delete](#method-delete)
- [Input parameters](#input-parameters)
  - [Simple parameters](#parameters-simple)
  - [Complex parameters](#parameters-complex)
  - [Nested parameters](#parameters-nested)
  - [Validation rules](#parameters-validation-rules)
  - [Validating method parameters](#parameters-validation-methods)
- [Setup](#setup)


<a name="controller-definition"></a>
## Controller definition

Controller is basically a Nette Presenter. It extends `Varhall\Restino\Controllers\RestController` class. 
There are 5 key endpoints methods `index`, `get`, `create`, `update` and `delete`. These standard methods
mapped to HTTP methods `GET`, `POST`, `PUT` and `DELETE`.

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

        public function get(int $id): User
        {
            return User::find($id);
        }
        
        public function create(array $data): User
        {
            return User::create($data);
        }

        public function create(int $id, array $data): User
        {
            return User::find($id)->update($data);
        }

        public function delete(int $id): void
        {
            User::find($id)->delete();
        }
    }


<a name="endpoint-methods"></a>
## Endpoint methods

<a name="method-index"></a>
### Index

The `index` method is mapped to HTTP method `GET` without ID parameter and is intended to retrieve a collection of models.
The result can be anything not only the `ICollection`. The typical result is `Nette\Database\Table\Selection` or `array`.
But in some API designs it can be also single object instance or primitive value.

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

<a name="method-get"></a>
### Get

The `get` method is mapped to HTTP method `GET` with ID parameter and is intended to retrieve a single model. The result
can be anything not only the `Varhall\Dbino\Model`. The typical result is `Nette\Database\Table\ActiveRow` or `object`.

    <?php

    namespace App\Presenters;

    use App\Models\User;
    use Varhall\Restino\Controllers\RestController;
    use Varhall\Utilino\Collections\ICollection;

    class UsersPresenter extends RestController
    {
        public function get(int $id): User
        {
            return User::find($id);
        }
    }

<a name="method-create"></a>
### Create

The `create` method is mapped to HTTP method `POST` without ID parameter and is intended to create a new model. The result
can be anything not only the `Varhall\Dbino\Model`. The typical result is `Nette\Database\Table\ActiveRow` or confirmation message.

    <?php

    namespace App\Presenters;

    use App\Models\User;
    use Varhall\Restino\Controllers\RestController;
    use Varhall\Utilino\Collections\ICollection;

    class UsersPresenter extends RestController
    {
        public function create(array $data): User
        {
            return User::create($data);
        }
    }

<a name="method-update"></a>
### Update

The `update` method is mapped to HTTP method `PUT` with ID parameter and is intended to update an existing model. The result
can be anything not only the `Varhall\Dbino\Model`. The typical result is `Nette\Database\Table\ActiveRow` or confirmation message.

    <?php

    namespace App\Presenters;

    use App\Models\User;
    use Varhall\Restino\Controllers\RestController;
    use Varhall\Utilino\Collections\ICollection;

    class UsersPresenter extends RestController
    {
        public function update(int $id, array $data): User
        {
            return User::find($id)->update($data);
        }
    }

<a name="method-delete"></a>
### Delete

The `delete` method is mapped to HTTP method `DELETE` with ID parameter and is intended to delete an existing model. The result
can be anything not only the `Varhall\Dbino\Model`. The typical result is `Nette\Database\Table\ActiveRow` or confirmation message.

    <?php

    namespace App\Presenters;

    use App\Models\User;
    use Varhall\Restino\Controllers\RestController;
    use Varhall\Utilino\Collections\ICollection;

    class UsersPresenter extends RestController
    {
        public function delete(int $id): void
        {
            User::find($id)->delete();
        }
    }


<a name="input-parameters"></a>
## Input parameters

API endpoint methods can have input parameters. These parameters are automatically filled from request data.
There automatic mapping and validation of input parameters. The input parameters can be defined using. Values
are taken from query string in case of `GET` and `DELETE` methods and from request body in JSON in case of `POST` 
and `PUT` methods.

<a name="parameters-simple"></a>
### Simple parameters

    <?php

    namespace App\Presenters;

    use App\Models\User;
    use Varhall\Restino\Controllers\RestController;
    use Varhall\Utilino\Collections\ICollection;

    class UsersPresenter extends RestController
    {
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

    namespace App\Presenters;

    use App\Models\User;
    use Varhall\Restino\Controllers\RestController;
    use Varhall\Utilino\Collections\ICollection;

    class UsersPresenter extends RestController
    {
          public function create(UserInput $data): User
          {
                // the code    
          }
    }

Class `UserInput` can look like this:

    <?php

    namespace App\Presenters;

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

    namespace App\Presenters;

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

    namespace App\Presenters;

    use Varhall\Restino\Attributes\Rule;
    
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

    namespace App\Presenters;

    use Varhall\Restino\Attributes\Rule;
    
    class UsersPresenter extends RestController
    {
        public function index(#[Rule('string:3..')] string $type): ICollection
        {
            return User::all()->where('type', $type)->where('enabled', $enabled);
        }
    }

<a name="setup"></a>
## Setup

Controller has special method `setup` which is called before any endpoint method. It can be used to configure
the controller. Typical usage is to register or configure middleware.

    <?php

    namespace App\Presenters;

    use App\Models\User;
    use Varhall\Restino\Controllers\RestController;
    use Varhall\Utilino\Collections\ICollection;

    class UsersPresenter extends RestController
    {
        public function setup(): void
        {
            $this->middleware('authentication')->except('create');
        }
    }

