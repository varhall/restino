# Changelog

## 1.3.0 (unreleased)

### Added

- **Versioning** of API clients (opt-in, section `restino.versioning`) - see [documentation](.docs/versioning.md)
  - client version from a request header or a custom `VersionResolver`, `ClientVersion` service
  - minimum version check with configurable rejection (`426` by default), `#[MinVersion]`, `#[Unversioned]`
  - advertised headers `X-Api-Min-Version` / `X-Api-Current-Version` and `Vary`
  - input adapters `#[Upgrade]`, output adapters `#[Downgrade]` on response classes and actions,
    step-by-step conversion of nested objects
  - version-based routing - `since` / `before` arguments of `#[Get]`, `#[Post]`, `#[Put]`, `#[Delete]`
  - `Inventory` for detecting dead adapters, PHPStan rule for `ClientVersion::before()` branches
    (`phpstan/extension.neon`)
- `ApiRouterFactory` service creating `ApiRouter` from controller attributes
- Filter order constraints `Configuration::after()` / `before()`, `Chain::getOrderedFilters()`, `Chain::has()`
- `Cors` option `expose_headers` and `CorsHeaderProvider` services
- `Context::getEndpoint()`
- `Serializer` registered as a service (`restino.serializer`), `AbstractResult::setSerializer()` / `getSerializer()`
- Configurable page size of collections - `restino.collection.defaultLimit` (100) and `maxLimit` (1000, `null` =
  unlimited), per result `CollectionResult::setLimits()`

### Changed

- `Dispatcher` removes filters created from attributes from the shared `Chain` after the chain is built. Previously
  they stayed registered and a second dispatch in the same process (workers, integration tests) failed with
  *Filter with name ... is already registered*.
- `SchemaGenerator` allows the same method and path for endpoints with non-overlapping version ranges.
- `Expand` serializes objects with the serializer of the result (`Expand::map()` has an optional third argument).
  The JSON output is unchanged.
- `Cors` reads `CorsHeaderProvider` services from the container.

### Fixed

- `Configuration::only()` / `except()` with a list of actions registered only the last one.

### Documentation

- New chapters [Responses](.docs/results.md) and [Versioning](.docs/versioning.md).
- Endpoints: input mapping rules (merged parameters, `array` parameters, missing values, defaults of action
  parameters), all validation attributes, validation error format, raw request.
- Filters: overview of bundled filters, correct `Collection` output (pagination headers), `Expand` with
  `IExpandable`, `Map` filter, security note for `Filter`, filter parameters via services, CORS preflight routing,
  filter order, working examples of `Closure` and custom filters.
- Fixed examples (namespaces, imports, missing quotes, removing filters, route parameters, `Rule` arguments).
