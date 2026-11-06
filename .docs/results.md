# Responses

- [Return values](#return-values)
- [Serialization](#serialization)
- [Response classes](#response-classes)
- [Collections](#collections)
  - [Limits](#collection-limits)
- [Status codes and errors](#status-codes)
  - [Termination](#termination)
  - [Abort](#abort)
  - [Other status codes](#other-status-codes)
  - [Exceptions](#exceptions)
- [Custom results](#custom-results)


<a name="return-values"></a>
## Return values

Every response of Restino is JSON. The value returned by the action is converted to a result object, which is
serialized after all [filters](filters.md) finished:

| Returned value | Result | Response |
| --- | --- | --- |
| `Varhall\Restino\Results\IResult` | used as it is | defined by the result |
| `Varhall\Utilino\Collections\ICollection`, `Nette\Database\Table\Selection` | `CollectionResult` | paginated JSON array, see [collections](#collections) |
| anything else (object, array, scalar, `null`) | `SimpleResult` | serialized value, `200 OK` |

An action declared as `void` returns `null`.


<a name="serialization"></a>
## Serialization

Values are converted to JSON recursively:

| Value | Serialized as |
| --- | --- |
| scalar, `null` | the value |
| `Varhall\Utilino\ISerializable` | result of `toArray()`, nested values serialized recursively |
| `Nette\Database\Table\ActiveRow` | result of `toArray()` |
| array, `Traversable` | array with serialized items |
| other object | public properties (or `jsonSerialize()` of `JsonSerializable`) |

Lists (arrays with keys `0..n`, iterators) are returned as JSON arrays, associative arrays as JSON objects.

Mappers added to the result by filters (e.g. [Map](filters.md#map-filter), [Expand](filters.md#expand-filter)) are
applied to each item before serialization.

With [versioning](versioning.md#downgrading-output) enabled, the serializer converts the output to the version of the
client.


<a name="response-classes"></a>
## Response classes

Returning models directly exposes all their columns. A response class defines exactly what the client gets and
is the place for formatting:

    <?php

    namespace App\Api\Responses;

    use App\Models\User;
    use Varhall\Utilino\ISerializable;

    class UserResponse implements ISerializable
    {
        public function __construct(private User $user) {}

        public function toArray(): array
        {
            return [
                'id'        => $this->user->id,
                'name'      => "{$this->user->name} {$this->user->surname}",
                'created'   => $this->user->created->format(DATE_ATOM),
                'address'   => $this->user->address ? new AddressResponse($this->user->address) : null,
            ];
        }
    }

    #[Get('/{id}')]
    public function detail(int $id): UserResponse
    {
        return new UserResponse(User::find($id));
    }

Nested response classes are serialized recursively. For collections use the [Map filter](filters.md#map-filter)
(`#[Map(UserResponse::class)]`), which wraps every item.

Response classes can also offer optional relations to the client, see [Expand filter](filters.md#expand-filter),
and carry [versioning adapters](versioning.md#downgrade-classes).


<a name="collections"></a>
## Collections

An action returning a collection (`ICollection`, `Selection`) produces `CollectionResult`. It is always paginated:

- the default page size is 100 items, the maximum is 1000 (see [limits](#collection-limits)),
- pagination is described in response headers `X-Limit`, `X-Offset`, `X-Total`, `X-Next-Offset` and
  `X-Previous-Offset` (the last two are empty on the last / first page),
- the body is a JSON array of items.

The client controls the page and ordering with `_limit`, `_offset` and `_order` query parameters when the
[Collection filter](filters.md#collection-filter) is enabled. Rows can be filtered by query parameters with the
[Filter filter](filters.md#filter-filter).

To return a whole array without pagination, return an array (`iterator_to_array($collection)` or a list of response
objects) instead of the collection.

<a name="collection-limits"></a>
### Limits

The page size used when the client does not send `_limit` and the maximal page size the client can request are
configured in `config.neon`:

    restino:
        collection:
            defaultLimit: 100       # page size without _limit, at least 1
            maxLimit: 1000          # maximal _limit; null = unlimited

A larger `_limit` is silently reduced to `maxLimit` - the client sees the real page size in `X-Limit`.

**Think twice before `maxLimit: null`.** Without the maximum, a single request with a huge `_limit` loads the
whole table into memory, serializes it (including mappers, expansions and versioning conversions) and sends it to the
client - an easy way to overload the server or to download all data at once. Prefer a reasonable global limit and
raise it only for endpoints which really need it (exports, internal clients). Limits set on the returned result take
precedence over the configuration (`null` maximum = unlimited):

    #[Get('/export')]
    #[Secured]
    public function export(): CollectionResult
    {
        return (new CollectionResult(Order::all()))->setLimits(1000, 50000);
    }

An action can also return a plain array (`iterator_to_array($collection)`), which is not paginated at all.


<a name="status-codes"></a>
## Status codes and errors

<a name="termination"></a>
### Termination

`Varhall\Restino\Results\Termination` returns the given data with the given HTTP code (`400 Bad Request` by
default). It is used for errors and is returned by filters which stop the request (`Secured`, `Role`, validation).

    use Nette\Http\IResponse;
    use Varhall\Restino\Results\Termination;

    #[Get('/{id}')]
    public function detail(int $id): UserResponse|Termination
    {
        $user = User::find($id);

        if (!$user) {
            return new Termination([ 'message' => 'User not found' ], IResponse::S404_NotFound);
        }

        return new UserResponse($user);
    }

The data of a termination is sent as it is - it is not serialized, mappers are not applied and versioning does not
convert it. Use arrays and scalars.

Invalid input parameters end with a termination as well, see [validation errors](endpoints.md#parameters-validation-errors).

<a name="abort"></a>
### Abort

When the error is detected deeper in the code called by the action, throw `Varhall\Restino\Results\Abort` with the
result to return. The action is stopped and the result is sent to the client.

    use Nette\Http\IResponse;
    use Varhall\Restino\Results\Abort;
    use Varhall\Restino\Results\Termination;

    class UserService
    {
        public function get(int $id): User
        {
            return User::find($id)
                ?? throw new Abort(new Termination([ 'message' => 'User not found' ], IResponse::S404_NotFound));
        }
    }

`Abort` is caught around the action only - filters return a `Termination` instead of throwing.

<a name="other-status-codes"></a>
### Other status codes

Successful responses have `200 OK`. To use another code with a serialized body (e.g. `201 Created`), set it on the
HTTP response and return the value as usual:

    use Nette\Http\IResponse;

    class UsersController implements IController
    {
        public function __construct(private IResponse $response) {}

        #[Post]
        public function create(UserInput $data): UserResponse
        {
            $user = User::create((array) $data);
            $this->response->setCode(IResponse::S201_Created);

            return new UserResponse($user);
        }
    }

<a name="exceptions"></a>
### Exceptions

Other exceptions are not handled by Restino and are passed to the Nette Application error handling
(`application › errorPresenter`). Configure an error presenter which returns JSON for API requests, otherwise
clients receive the HTML error page.


<a name="custom-results"></a>
## Custom results

A custom result implements `Varhall\Restino\Results\IResult`:

    <?php

    namespace App\Api\Results;

    use Nette\Http\IRequest;
    use Nette\Http\IResponse;
    use Varhall\Restino\Results\IResult;

    class CreatedResult implements IResult
    {
        public function __construct(private array $data, private string $location) {}

        public function getData(): mixed
        {
            return $this->data;
        }

        public function execute(IResponse $response, IRequest $request): mixed
        {
            $response->setCode(IResponse::S201_Created);
            $response->setHeader('Location', $this->location);

            return $this->data;     // encoded to JSON
        }
    }

`getData()` gives filters access to the returned value, `execute()` sets the response and returns the data encoded
to JSON. Extend `Varhall\Restino\Results\AbstractResult` instead to support mappers and the serializer
(`$this->serialize($value)`), which is required for versioning.
