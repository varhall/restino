# Versioning

- [Introduction](#introduction)
- [Quick start](#quick-start)
- [Configuration reference](#configuration)
- [Determining client version](#client-version-resolving)
  - [Missing and invalid version](#missing-version)
  - [Custom resolver](#custom-resolver)
- [ClientVersion service](#client-version-service)
- [Minimum version and rejection](#minimum-version)
  - [Endpoint minimum](#endpoint-minimum)
  - [Unversioned endpoints](#unversioned)
  - [Custom rejection](#custom-rejection)
- [Filter order and CORS](#filter-order)
- [Version-based routing](#routing)
- [Upgrading input](#upgrading-input)
- [Downgrading output](#downgrading-output)
  - [Response classes](#downgrade-classes)
  - [Step-by-step conversion](#downgrade-steps)
  - [Collections and expansions](#downgrade-collections)
  - [Whole action output](#downgrade-action)
- [Keeping it clean](#keeping-it-clean)
  - [Inventory test](#inventory)
  - [PHPStan extension](#phpstan)
- [Testing your API](#testing)
- [Step-by-step: your first breaking change](#tutorial)
- [Limitations](#limitations)
- [FAQ and troubleshooting](#faq)


<a name="introduction"></a>
## Introduction

Some API clients cannot be updated at the moment the server changes. A mobile application waits for the store
review and users update it days or weeks later, a partner integration is updated whenever its authors find time.
Meanwhile the server keeps evolving - fields are renamed, objects are restructured, endpoints are replaced.

Restino versioning lets one codebase serve old and new clients at the same time. The client sends the version
of the API contract it was built against and Restino adapts every request and response to it:

- **contract version** - a positive integer identifying the shape of the API (requests and responses).
  It is increased whenever an incompatible change is released.
- **current** - the newest contract version, the one your code is written for.
- **minimum** - the oldest contract version which is still served. Older clients are rejected and asked to update.
- **client version** - the version declared by the client in a request header.
- **adapter** - a small class converting data between two neighbouring versions. *Upgraders* convert old input
  to the current shape, *downgraders* convert current output to the old shape.

Your controllers and response classes are always written for the **current** version. Adapters are the only
place which knows about older versions.

How a request flows through Restino:

    HTTP request (X-Api-Version: 2)
        │
        ▼
    ApiRouter ............ resolves client version, picks endpoint valid for version 2
        │
        ▼
    filters (Cors, ...)
        │
        ▼
    VersioningFilter ..... rejects clients below minimum, upgrades input 2 → current
        │
        ▼
    controller action .... works with the current shape only
        │
        ▼
    serializer ........... downgrades output current → 2
        │
        ▼
    HTTP response

Versioning is **opt-in**. Without the `versioning` section in the configuration nothing is checked, converted
or added to responses.


<a name="quick-start"></a>
## Quick start

1. Enable versioning in `config.neon`:

        restino:
            versioning:
                current: 1
                minimum: 1

2. Create the router using `ApiRouterFactory`, so the router knows the client version:

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

3. Clients send the version in every request:

        GET /api/books/7
        X-Api-Version: 1

        HTTP/1.1 200 OK
        X-Api-Min-Version: 1
        X-Api-Current-Version: 1
        Vary: X-Api-Version

        { "title": "R.U.R.", "author": { "firstName": "Karel", "lastName": "Capek" } }

    A request without the header is rejected:

        GET /api/books/7

        HTTP/1.1 426 Upgrade Required
        X-Api-Min-Version: 1
        X-Api-Current-Version: 1

        { "error": "client_outdated", "min_version": 1, "current_version": 1 }

That's all for the first release. Adapters come into play with the first incompatible change - see
[the tutorial](#tutorial).


<a name="configuration"></a>
## Configuration reference

    restino:
        versioning:
            current: 5
            minimum: 3
            header: X-Api-Version
            missing: reject
            rejection:
                code: 426
            advertise:
                minimum: X-Api-Min-Version
                current: X-Api-Current-Version
            vary: true
            position:
                after: [ Varhall\Restino\Filters\Cors ]
                before: []

| Option | Type | Default | Description |
| --- | --- | --- | --- |
| `current` | int | required | The newest contract version. |
| `minimum` | int | required | The oldest served version, `1 ≤ minimum ≤ current`. |
| `header` | string | `X-Api-Version` | Request header with the client version (used by the default resolver). |
| `missing` | `reject` \| `minimum` \| `current` | `reject` | What to do when the client does not send the version, see [below](#missing-version). |
| `rejection.code` | int | `426` | HTTP status for clients below the minimum. |
| `advertise` | `false` or structure | both headers | Response headers advertising supported versions. Set a header to `false` (or the whole option) to disable it. |
| `vary` | bool | `true` | Add the version header to the `Vary` response header. |
| `position.after` | list | `[Cors]` | Filters (names or classes) which run before (outside) the versioning filter. |
| `position.before` | list | `[]` | Filters (names or classes) which run after (inside) the versioning filter. |

The configuration is validated when the container is compiled - for example `minimum` greater than `current`
fails immediately.

Recommended combinations:

| Use case | Configuration |
| --- | --- |
| Mobile application, version is mandatory | `missing: reject`, `rejection: { code: 426 }` |
| Public API, version is optional | `missing: current` (clients without the header get the newest API) or `missing: minimum` |
| Public API, removed versions | `rejection: { code: 410 }` |

Values can be overridden per environment, for example in `local.neon` for a testing stack:

    restino:
        versioning:
            minimum: 1


<a name="client-version-resolving"></a>
## Determining client version

By default the version is read from the header configured in `header`. The value must be a positive integer.

    X-Api-Version: 3

<a name="missing-version"></a>
### Missing and invalid version

When the header is missing, the `missing` policy decides:

| Policy | Effective version | Typical use |
| --- | --- | --- |
| `reject` | `0` | Every client must declare the version, others are rejected. |
| `minimum` | `minimum` | Unknown clients are treated as the oldest supported ones. |
| `current` | `current` | Unknown clients get the newest API. |

An **invalid value** (`abc`, `1.2`, `-1`, empty) always results in version `0`, regardless of the policy.

Version `0` means *unknown client*. Because `minimum` is always at least 1, such a client is always rejected
(except on [unversioned endpoints](#unversioned)).

<a name="custom-resolver"></a>
### Custom resolver

The version can come from anywhere - a query parameter, the `Accept` header, a token. Implement
`VersionResolver` and register it as a service. It replaces the default header resolver automatically.

    <?php

    namespace App\Api;

    use Nette\Http\IRequest;
    use Varhall\Restino\Versioning\VersionResolver;

    /**
     * Accept: application/vnd.example+json; v=3
     */
    class AcceptVersionResolver implements VersionResolver
    {
        public function resolve(IRequest $request): ?int
        {
            $accept = $request->getHeader('Accept');

            if ($accept === null || !preg_match('/;\s*v=([^;,\s]*)/', $accept, $matches)) {
                return null;                                      // version not declared
            }

            return ctype_digit($matches[1]) ? (int) $matches[1] : 0;   // 0 = invalid
        }

        public function varyHeaders(): array
        {
            return [ 'Accept' ];                                  // response depends on this header
        }
    }

    services:
        - App\Api\AcceptVersionResolver

Return `null` when the client did not declare any version (the `missing` policy applies) and `0` for an invalid
value. `varyHeaders()` lists request headers the response depends on; they are added to `Vary` and to the CORS
`Access-Control-Allow-Headers`.


<a name="client-version-service"></a>
## ClientVersion service

`Varhall\Restino\Versioning\ClientVersion` holds the version of the current client. It is autowired, so you can
inject it to controllers, services and filters.

| Method | Description |
| --- | --- |
| `get(): int` | Effective version (after the `missing` policy). |
| `before(int $version): bool` | `true` when the client speaks an older contract than `$version`. |
| `declared(): ?int` | The value the client really sent - `null` when not sent, `0` when invalid. Useful for logging. |
| `isResolved(): bool` | Whether the version was already read from the request. |
| `resolve(IRequest $request): void` | Reads the version from the request. Called by `ApiRouter`. |
| `set(int $version): void` | Sets the version manually (CLI, queues, tests). |
| `reset(): void` | Returns to the initial state. |

**When is the version known?** `ApiRouter` resolves it while matching the request, before any filter or controller
runs. (If you create the router without `ApiRouterFactory`, `Dispatcher` resolves it as a fallback.)

**Outside of API requests** - console commands, queue workers, tests of services - the version is not resolved
and `get()` returns `current`. Serializing a response in a background job therefore produces the current shape.
Use `set()` when you need a particular version there.

Branching in a service (prefer adapters, use this for behaviour, not for the shape of data):

    <?php

    namespace App\Model;

    use Varhall\Restino\Versioning\ClientVersion;

    class OrderService
    {
        public const VERSION_PARTIAL_PAYMENTS = 4;

        public function __construct(private ClientVersion $version) {}

        public function paymentOptions(Order $order): array
        {
            if ($this->version->before(self::VERSION_PARTIAL_PAYMENTS)) {
                return [ 'full' ];        // old clients cannot display partial payments
            }

            return [ 'full', 'partial' ];
        }
    }

Use integer literals or class constants as the argument of `before()` - the [PHPStan extension](#phpstan) checks
them.

Logging the declared version in a custom filter:

    <?php

    namespace App\Api;

    use Psr\Log\LoggerInterface;
    use Varhall\Restino\Filters\Context;
    use Varhall\Restino\Filters\IFilter;
    use Varhall\Restino\Results\IResult;
    use Varhall\Restino\Versioning\ClientVersion;

    class RequestLogFilter implements IFilter
    {
        public function __construct(private ClientVersion $version, private LoggerInterface $logger) {}

        public function execute(Context $context, callable $next): IResult
        {
            $result = $next($context);

            $this->logger->info('API request', [
                'endpoint'          => $context->getEndpoint()->path,
                'client_version'    => $this->version->declared(),
            ]);

            return $result;
        }
    }


<a name="minimum-version"></a>
## Minimum version and rejection

`VersioningFilter` is registered automatically. For every request it:

1. lets `OPTIONS` requests (CORS preflight) pass,
2. lets [unversioned endpoints](#unversioned) pass,
3. adds advertised headers (`X-Api-Min-Version`, `X-Api-Current-Version`) and `Vary`,
4. rejects clients below the required version,
5. [upgrades the input](#upgrading-input).

A rejected client receives:

    HTTP/1.1 426 Upgrade Required
    X-Api-Min-Version: 3
    X-Api-Current-Version: 5
    Vary: X-Api-Version

    { "error": "client_outdated", "min_version": 3, "current_version": 5 }

Advertised headers are sent with every versioned response, including rejections. Recommended client behaviour:

- **rejected** (`426`) - show a blocking "please update" screen,
- **client version < `X-Api-Current-Version`** - show a non-blocking "new version available" hint.

<a name="endpoint-minimum"></a>
### Endpoint minimum

A single endpoint can require a newer client than the global minimum. The effective minimum is the higher one.

    <?php

    use Varhall\Restino\Controllers\Attributes\Get;
    use Varhall\Restino\Versioning\Attributes\MinVersion;

    #[Get('/recommended')]
    #[MinVersion(4)]
    public function recommended(): array
    {
        // ...
    }

`#[MinVersion]` can be used on the controller class as well. It is also the right tool for a **removed endpoint**:
keep a stub with `#[MinVersion(n)]` instead of deleting the method, so old clients get the "please update" answer
instead of `404`.

<a name="unversioned"></a>
### Unversioned endpoints

Some endpoints are called by browsers or third parties which never send the version header - file downloads,
images in `<img src>`, documentation, payment gateway webhooks. Mark them with `#[Unversioned]` (on a method or
the whole controller). They are not checked, get no advertised headers and their input is not upgraded.

    <?php

    use Varhall\Restino\Controllers\Attributes\Get;
    use Varhall\Restino\Controllers\Attributes\Path;
    use Varhall\Restino\Controllers\IController;
    use Varhall\Restino\Versioning\Attributes\Unversioned;

    #[Path('/api/files')]
    #[Unversioned]
    class FilesController implements IController
    {
        #[Get('/{id}')]
        public function download(string $id): FileResponse
        {
            // ...
        }
    }

<a name="custom-rejection"></a>
### Custom rejection

Implement `RejectionFactory` and register it as a service to change the response for outdated clients.

    <?php

    namespace App\Api;

    use Varhall\Restino\Results\IResult;
    use Varhall\Restino\Versioning\ClientOutdated;
    use Varhall\Restino\Versioning\ClientVersion;
    use Varhall\Restino\Versioning\RejectionFactory;

    class StoreLinkRejectionFactory implements RejectionFactory
    {
        public function create(ClientVersion $version, int $required): IResult
        {
            return new ClientOutdated([
                'message'   => 'Please update the application.',
                'store'     => 'https://apps.example.com/our-app',
                'required'  => $required,
            ], $version->getSettings()->rejectionCode);
        }
    }

    services:
        - App\Api\StoreLinkRejectionFactory


<a name="filter-order"></a>
## Filter order and CORS

The versioning filter is registered under the name `versioning` and runs **inside the `Cors` filter** (option
`position.after`). The order is resolved when the request is dispatched, so it works even when `Cors` is added
later, for example in a controller `setup()` method. See [filter order](filters.md#filter-order).

Why it matters for browser clients:

- the CORS preflight (`OPTIONS`) never carries custom headers - it must not be rejected,
- the rejection must carry CORS headers, otherwise the browser hides it and the client cannot show "please update".

Both is handled automatically: the versioning filter never rejects `OPTIONS` requests and runs inside `Cors`, so
rejections carry CORS headers. In addition, the version header is added to `Access-Control-Allow-Headers` and
the advertised headers to `Access-Control-Expose-Headers` of every `Cors` filter (see
[CORS header providers](filters.md#cors-header-providers)). How preflight requests reach the application at all is
described in [CORS filter](filters.md#cors-filter).

If you need other filters to run before versioning (typically request logging, so rejections are logged too),
list them in `position.after`. Keep `Cors` in the list if you use it:

    restino:
        versioning:
            position:
                after: [ Varhall\Restino\Filters\Cors, requestlog ]

When CORS is handled outside of Restino (web server, `index.php`), add the version header to
`Access-Control-Allow-Headers` and the advertised headers to `Access-Control-Expose-Headers` there.

**Vary.** The same URL returns different data for different versions. The `Vary: X-Api-Version` header tells
HTTP caches and CDNs to store a separate copy per version. Without it, a cache could serve a response for version 5
to a version 3 client.


<a name="routing"></a>
## Version-based routing

When the behaviour of an endpoint changes completely, it is cleaner to write a new action than to convert data.
Two actions can share the same method and path if their version ranges do not overlap:

    <?php

    use Varhall\Restino\Controllers\Attributes\Post;

    #[Post('/{id}/verify', before: 5)]        // clients 1 - 4
    public function verifyLegacy(string $id): InvitationResponse
    {
        // ...
    }

    #[Post('/{id}/verify', since: 5)]         // clients 5 and newer
    public function verify(string $id, VerifyRequest $data): InvitationResponse
    {
        // ...
    }

- `since: n` - the endpoint is available for versions `>= n`,
- `before: n` - the endpoint is available for versions `< n`,
- both can be combined (`since: 2, before: 4` = versions 2 and 3),
- without them the endpoint is available for all versions.

Overlapping ranges are detected by `SchemaGenerator` when the schema is built:

    Duplicate endpoint detected: [POST] /invitations/{id}/verify (InvitationsController::verify, since 4)
    and [POST] /invitations/{id}/verify (InvitationsController::verifyLegacy, before 5)

A client whose version matches no variant gets `404`. Version ranges are ignored when the router is created
without versioning (`new ApiRouter($schema)` or versioning disabled).

Which tool to use:

| Change | Tool |
| --- | --- |
| Field renamed, split, merged, moved | [adapters](#upgrading-input) |
| New required input field with a sensible default | upgrade adapter filling the default |
| Different behaviour, different validation, different side effects | new action with `since` / `before` |
| Endpoint removed | stub with `#[MinVersion]` |

> The router takes the first endpoint whose path matches. Declare actions with fixed paths (`/catalog`) before
> actions with parameters (`/{id}`) in the same controller.


<a name="upgrading-input"></a>
## Upgrading input

Old clients send input in an old shape. An **upgrader** converts the input of version `n - 1` to version `n`.
It is attached to the action with `#[Upgrade(before: n, with: ...)]`:

    <?php

    namespace App\Api\Adapters;

    use Varhall\Restino\Versioning\Upgrader;

    /**
     * Version 2 sent { "name": "Karel Capek" }, version 3 expects { "firstName": "Karel", "lastName": "Capek" }
     */
    class SplitAuthorNameUpgrader implements Upgrader
    {
        public function __invoke(array $parameters): array
        {
            if (isset($parameters['name'])) {
                [ $parameters['firstName'], $parameters['lastName'] ] = array_pad(explode(' ', $parameters['name'], 2), 2, '');
                unset($parameters['name']);
            }

            return $parameters;
        }
    }

    #[Post('/authors')]
    #[Upgrade(before: 3, with: SplitAuthorNameUpgrader::class)]
    public function createAuthor(string $firstName, string $lastName): AuthorResponse
    {
        // written for version 3 only
    }

Upgraders are chained in **ascending** order. A client of version 1 calling an action with
`#[Upgrade(before: 2, ...)]` and `#[Upgrade(before: 3, ...)]` gets both, in this order:

| Client | Applied upgraders |
| --- | --- |
| 1 | before 2, before 3 |
| 2 | before 3 |
| 3 (current) | none |

What the upgrader receives:

- path parameters, query parameters and the JSON body merged into one array (the same data the action
  parameters are mapped from),
- without Restino internal keys (`_endpoint`, `action`, `presenter`) - you cannot break routing by accident.

Upgraders are created through the DI container, so they can have dependencies. Prefer pure functions over the
array anyway - they are easy to test and to delete.

Validation errors are reported in the **current** shape (`firstName is required`), because validation runs after
the upgrade.


<a name="downgrading-output"></a>
## Downgrading output

<a name="downgrade-classes"></a>
### Response classes

Responses are written in the current shape. A **downgrader** converts serialized output of version `n` to version
`n - 1`. It is attached to the response class (any `Varhall\Utilino\ISerializable`) with
`#[Downgrade(before: n, with: ...)]`:

    <?php

    namespace App\Api\Responses;

    use App\Api\Adapters\AuthorNameDowngrader;
    use Varhall\Restino\Versioning\Attributes\Downgrade;
    use Varhall\Utilino\ISerializable;

    #[Downgrade(before: 3, with: AuthorNameDowngrader::class)]
    class AuthorResponse implements ISerializable
    {
        public function __construct(private Author $author) {}

        public function toArray(): array
        {
            return [
                'firstName' => $this->author->first_name,
                'lastName'  => $this->author->last_name,
            ];
        }
    }

    <?php

    namespace App\Api\Adapters;

    use Varhall\Restino\Versioning\Downgrader;

    /**
     * Version 3: { firstName, lastName }  →  version 2: { name }
     */
    class AuthorNameDowngrader implements Downgrader
    {
        public function __invoke(array $data, mixed $source): array
        {
            $data['name'] = "{$data['firstName']} {$data['lastName']}";
            unset($data['firstName'], $data['lastName']);

            return $data;
        }
    }

`$data` is the serialized object, `$source` is the original object (`AuthorResponse`). Use `$source` when the old
shape needs something the new shape does not contain any more.

The same response for different clients:

    X-Api-Version: 3    { "firstName": "Karel", "lastName": "Capek" }
    X-Api-Version: 2    { "name": "Karel Capek" }

Downgraders are found on the class itself (attributes of parent classes are not inherited) and apply to every
place where the object appears - as the whole response, inside another response, in a list.

<a name="downgrade-steps"></a>
### Step-by-step conversion

With more versions and nested objects the order matters. Restino converts the whole response **one version at
a time**, from `current` down to the client version. In each step it walks the tree **from the top**: the parent
adapter runs first, then the adapters of its children.

The consequence: **every adapter sees its object and all nested objects exactly in the shape of its version.** An
adapter written for version 3 always gets version 3 data, no matter how old the client is.

Example with `current: 3`:

    #[Downgrade(before: 3, with: BookInitialsDowngrader::class)]   // adds "initials" computed from the author
    #[Downgrade(before: 2, with: BookRenameDowngrader::class)]     // "title" → "name"
    class BookResponse implements ISerializable { /* { title, author: AuthorResponse } */ }

    #[Downgrade(before: 3, with: AuthorNameDowngrader::class)]     // { firstName, lastName } → { name }
    #[Downgrade(before: 2, with: AuthorFullNameDowngrader::class)] // { name } → { fullName }
    class AuthorResponse implements ISerializable { /* { firstName, lastName } */ }

Conversion for a client of version 1:

| Step | Adapter | Book sees | Author sees |
| --- | --- | --- | --- |
| 3 → 2 | `BookInitialsDowngrader` | `{ title, author }` | `{ firstName, lastName }` |
| 3 → 2 | `AuthorNameDowngrader` | | `{ firstName, lastName }` |
| 2 → 1 | `BookRenameDowngrader` | `{ title, author, initials }` | `{ name }` |
| 2 → 1 | `AuthorFullNameDowngrader` | | `{ name }` |

Result: `{ "name": "...", "author": { "fullName": "Karel Capek" }, "initials": "KC" }`.

**Rule for adapter authors:** an adapter may read nested objects, add, remove and rename its own keys and even move
a nested object under another key. It should **not modify the content** of a nested object. A nested object changed
by the parent becomes plain data and its own adapters are no longer applied to it. Convert each object with its own
adapter.

Only `ISerializable` objects take part in the conversion. Arrays and scalars are passed through; plain objects and
database rows (`ActiveRow`) are serialized as before, but cannot have adapters.

<a name="downgrade-collections"></a>
### Collections and expansions

Lists and collections (`CollectionResult`) are converted item by item. Pagination headers are not affected.

Relations added by the [Expand filter](filters.md#expand-filter) are converted too - each expanded value with its
own adapters. The adapter of the expanded object does not see the expanded keys, they are added after it ran.

    GET /api/books/7?_expand=author
    X-Api-Version: 2

    { "title": "R.U.R.", "author": { "name": "Karel Capek" } }

<a name="downgrade-action"></a>
### Whole action output

Actions which return plain arrays have no response class to put the adapter on. Attach the downgrader to the
action instead - it receives the whole serialized output:

    #[Get('/stats')]
    #[Downgrade(before: 3, with: StatsDowngrader::class)]
    public function stats(): array
    {
        return [ 'total' => 10, 'active' => 7 ];
    }

    class StatsDowngrader implements Downgrader
    {
        public function __invoke(array $data, mixed $source): array
        {
            return [ 'count' => $data['total'] ];     // version 2 returned { count }
        }
    }

Action downgraders run after the response classes were converted, in descending order. Therefore **do not combine
them with response classes which have their own downgraders** - the action adapter would see those objects already
in the client shape. The [inventory test](#inventory) reports such combinations.

Termination results (validation errors, `401` from `Secured`, rejections) are never converted.


<a name="keeping-it-clean"></a>
## Keeping it clean

Every versioning construct carries a version number, so it is easy to find the ones which are no longer needed.
The life cycle of a change:

1. Release version `n`: increase `current`, write adapters `before: n`.
2. Old clients disappear (store statistics, request logs with `declared()` versions).
3. Increase `minimum` to `n`.
4. Remove everything which is now dead: adapters `before ≤ n`, `#[MinVersion(≤ n)]`, endpoints `before ≤ n`,
   `since ≤ n` restrictions and `ClientVersion::before(≤ n)` branches.

Step 4 is checked automatically by two tools.

<a name="inventory"></a>
### Inventory test

`Varhall\Restino\Versioning\Inspection\Inventory` inspects controllers and response classes and reports:

| Problem | Example |
| --- | --- |
| dead adapter | `#[Upgrade(before: 3)]` with `minimum: 3` |
| future version | `#[Downgrade(before: 6)]` with `current: 5` |
| redundant endpoint minimum | `#[MinVersion(3)]` with `minimum: 3` |
| unreachable endpoint | `#[Get('/x', before: 3)]` with `minimum: 3` |
| redundant restriction | `#[Get('/x', since: 3)]` with `minimum: 3` |
| duplicate adapters | two `#[Upgrade(before: 4)]` on one action |
| invalid adapter class | class does not exist or does not implement `Upgrader` / `Downgrader` |
| action downgrade combined with a response class with own downgraders | see [above](#downgrade-action) |

The inventory is registered in the container with the configured versions. Add one test to your application:

    <?php

    declare(strict_types=1);

    use Nette\Loaders\RobotLoader;
    use Tester\Assert;
    use Varhall\Restino\Versioning\Inspection\Inventory;

    require __DIR__ . '/../bootstrap.php';

    $container = App\Bootstrap::bootForTests()->createContainer();

    $loader = new RobotLoader();
    $loader->addDirectory(__DIR__ . '/../../app/Api');
    $loader->rebuild();

    $violations = $container->getByType(Inventory::class)->check(array_keys($loader->getIndexedClasses()));

    Assert::same([], array_map('strval', $violations));

A failure lists every problem with its location:

    App\Api\BooksController::createAuthor() #[Upgrade]: adapter before 3 is never applied, all clients have
    version >= 3 (minimum) - remove it

`Inventory::list($classes)` returns all versioning constructs - handy for an overview before increasing the minimum.

<a name="phpstan"></a>
### PHPStan extension

Branches in your code (`ClientVersion::before()`) are checked by a PHPStan rule. Include the extension and set the
same versions as in the application configuration:

    includes:
        - vendor/varhall/restino/phpstan/extension.neon

    parameters:
        restinoVersioning:
            minimum: 3
            current: 5

Reported problems:

    ClientVersion::before(3) is always false, minimum supported version is 3. Remove the dead branch.
    ClientVersion::before(6) refers to a future version, current version is 5.
    Argument of ClientVersion::before() must be a constant integer (literal or class constant), so the branch can be checked.

Keep the PHPStan parameters in sync with `config.neon` - the inventory test above can assert it, e.g. by parsing
`phpstan.neon` with `Nette\Neon\Neon::decodeFile()` and comparing it with the `Settings` service.


<a name="testing"></a>
## Testing your API

The version is just a request header, test requests set it like any other header. Recommended minimum for each
adapter: one test for the oldest supported version (`minimum`) and one for `current`.

Services which branch on the version can be tested with `ClientVersion::set()`:

    $version = $container->getByType(ClientVersion::class);
    $version->set(3);

    Assert::same([ 'full' ], $service->paymentOptions($order));

    $version->reset();

Adapters are plain classes - test them directly with arrays.


<a name="tutorial"></a>
## Step-by-step: your first breaking change

The application is released with version 1. The author endpoint returns `name`; the new design needs first and
last name separately.

**1. Starting point**

    restino:
        versioning:
            current: 1
            minimum: 1

    GET /api/authors/1              → { "name": "Karel Capek" }
    POST /api/authors { "name": "Karel Capek" }

**2. Change the code to the new shape and increase `current`**

    restino:
        versioning:
            current: 2
            minimum: 1

    class AuthorResponse implements ISerializable
    {
        public function toArray(): array
        {
            return [ 'firstName' => $this->author->first_name, 'lastName' => $this->author->last_name ];
        }
    }

    #[Post('/')]
    public function create(string $firstName, string $lastName): AuthorResponse { /* ... */ }

Version 1 clients are broken now. Fix them with adapters.

**3. Downgrade the output for version 1**

    class AuthorNameDowngrader implements Downgrader
    {
        public function __invoke(array $data, mixed $source): array
        {
            $data['name'] = trim("{$data['firstName']} {$data['lastName']}");
            unset($data['firstName'], $data['lastName']);

            return $data;
        }
    }

    #[Downgrade(before: 2, with: AuthorNameDowngrader::class)]
    class AuthorResponse implements ISerializable { /* ... */ }

**4. Upgrade the input of version 1**

    class SplitAuthorNameUpgrader implements Upgrader { /* name → firstName, lastName */ }

    #[Post('/')]
    #[Upgrade(before: 2, with: SplitAuthorNameUpgrader::class)]
    public function create(string $firstName, string $lastName): AuthorResponse { /* ... */ }

**5. Test both versions**

    GET /api/authors/1   X-Api-Version: 1   → { "name": "Karel Capek" }
    GET /api/authors/1   X-Api-Version: 2   → { "firstName": "Karel", "lastName": "Capek" }

The inventory test passes - adapters `before: 2` are valid for the window 1 - 2.

**6. Release** the server, then the client built against version 2.

**7. Raise the minimum** once version 1 clients are gone:

    restino:
        versioning:
            current: 2
            minimum: 2

The inventory test now fails:

    AuthorResponse #[Downgrade]: adapter before 2 is never applied, all clients have version >= 2 (minimum) - remove it
    AuthorsController::create() #[Upgrade]: adapter before 2 is never applied, ... - remove it

**8. Delete** both adapters and their attributes. The code is back to a single shape, ready for the next change.


<a name="limitations"></a>
## Limitations

| Limitation | Workaround |
| --- | --- |
| Validation errors and other `Termination` results are not converted; error keys use the current input shape. | Keep error messages generic for old clients, or introduce the field rename together with a new action (`since`). |
| Only `ISerializable` classes can have downgraders; plain objects, `JsonSerializable` and database rows cannot. | Wrap the data in a response class. |
| Downgraders are not inherited from parent classes. | Repeat the attribute on the subclass or share the adapter class. |
| A nested object modified by the parent adapter is no longer converted by its own adapters. | Convert each object by its own adapter. |
| Action downgraders cannot be combined with response classes having own downgraders. | Move the adapter to the response class. |
| API documentation generators do not know the converted shapes (adapters are code). | Document old shapes in the adapter docblock. |
| Versions are integers only. | Map your release numbers to contract versions. |


<a name="faq"></a>
## FAQ and troubleshooting

**The client gets 426 although it sends the header.**

- Check the header name (`header` option, or your custom resolver).
- Browser client: the CORS preflight must allow the header - use the `Cors` filter or add the header to
  `Access-Control-Allow-Headers` where CORS is handled.
- A proxy or load balancer may strip unknown headers.
- The value must be a positive integer; anything else is version `0`.

**An adapter is not applied.**

- `before` must be greater than the client version (`before: 3` applies to clients 1 and 2).
- Downgrader on a class: the object must implement `ISerializable` and the attribute must be on that class, not
  a parent.
- Outside of API requests (CLI, queues) nothing is converted unless you call `ClientVersion::set()`.
- The router must be created with `ApiRouterFactory` for version-based routing.

**Duplicate endpoint detected with version ranges.**

The ranges overlap. `before: 5` and `since: 5` do not overlap, `before: 5` and `since: 4` do (version 4).

**A request for `/books/catalog` ends in the `/books/{id}` action.**

The router takes the first matching endpoint. Declare fixed paths before parametrized ones.
