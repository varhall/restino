<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Tests\Fixtures\Versioning\Kit;
use Varhall\Restino\Filters\Context;
use Varhall\Restino\Results\IResult;
use Varhall\Restino\Results\SimpleResult;
use Varhall\Restino\Versioning\ClientOutdated;
use Varhall\Restino\Versioning\ClientVersion;
use Varhall\Restino\Versioning\Compatibility;
use Varhall\Restino\Versioning\DefaultRejectionFactory;
use Varhall\Restino\Versioning\HeaderVersionResolver;
use Varhall\Restino\Versioning\RejectionFactory;
use Varhall\Restino\Versioning\Settings;
use Varhall\Restino\Versioning\VersioningFilter;


function versioningFilter(ClientVersion $version, ?RejectionFactory $rejection = null): VersioningFilter
{
    return new VersioningFilter(
        new Compatibility($version),
        new HeaderVersionResolver(),
        $rejection ?? new DefaultRejectionFactory()
    );
}

function passed(): callable
{
    return fn(Context $context) => new SimpleResult($context->getRequest()->getParameters());
}


Toolkit::test(function (): void {
    [$response, $headers] = Kit::response();
    $filter = versioningFilter(Kit::version(null));

    $result = $filter->execute(Kit::context(Kit::endpoint('detail'), $response, [], 'OPTIONS'), passed());

    Assert::type(SimpleResult::class, $result);
    Assert::count(0, $headers);
}, 'VersioningFilter: CORS preflight passes without check');


Toolkit::test(function (): void {
    [$response, $headers] = Kit::response();
    $filter = versioningFilter(Kit::version(null));

    $result = $filter->execute(Kit::context(Kit::endpoint('cover'), $response), passed());

    Assert::type(SimpleResult::class, $result);
    Assert::count(0, $headers);
}, 'VersioningFilter: #[Unversioned] endpoint passes without check and headers');


Toolkit::test(function (): void {
    [$response, $headers] = Kit::response();
    $filter = versioningFilter(Kit::version('1', Kit::settings(3, 2)));

    $result = $filter->execute(Kit::context(Kit::endpoint('detail'), $response), passed());

    Assert::type(ClientOutdated::class, $result);
    Assert::same(426, $result->getCode());
    Assert::same([
        'error'             => 'client_outdated',
        'min_version'       => 2,
        'current_version'   => 3,
    ], $result->getData());

    // client gets to know where to upgrade even when rejected
    Assert::same('2', $headers['X-Api-Min-Version']);
    Assert::same('3', $headers['X-Api-Current-Version']);
    Assert::same('X-Api-Version', $headers['Vary']);
}, 'VersioningFilter: client below global minimum is rejected');


Toolkit::test(function (): void {
    [$response] = Kit::response();
    $filter = versioningFilter(Kit::version(null, Kit::settings(3, 1)));

    $result = $filter->execute(Kit::context(Kit::endpoint('detail'), $response), passed());

    Assert::type(ClientOutdated::class, $result);
}, 'VersioningFilter: missing version with reject policy is rejected');


Toolkit::test(function (): void {
    [$response] = Kit::response();

    $old = versioningFilter(Kit::version('2'))->execute(Kit::context(Kit::endpoint('recommended'), $response), passed());
    $new = versioningFilter(Kit::version('3'))->execute(Kit::context(Kit::endpoint('recommended'), $response), passed());

    Assert::type(ClientOutdated::class, $old);
    Assert::same(3, $old->getData()['min_version']);
    Assert::type(SimpleResult::class, $new);
}, 'VersioningFilter: #[MinVersion] higher than global minimum');


Toolkit::test(function (): void {
    [$response, $headers] = Kit::response([ 'Vary' => 'Accept-Encoding, x-api-version' ]);
    versioningFilter(Kit::version('3'))->execute(Kit::context(Kit::endpoint('detail'), $response), passed());

    Assert::same('Accept-Encoding, x-api-version', $headers['Vary']);

    [$response, $headers] = Kit::response([ 'Vary' => 'Accept-Encoding' ]);
    versioningFilter(Kit::version('3'))->execute(Kit::context(Kit::endpoint('detail'), $response), passed());

    Assert::same('Accept-Encoding, X-Api-Version', $headers['Vary']);
}, 'VersioningFilter: Vary is appended to existing value only once');


Toolkit::test(function (): void {
    [$response, $headers] = Kit::response();
    $settings = new Settings(3, 1, advertiseMinimum: null, advertiseCurrent: null, vary: false);

    versioningFilter(Kit::version('3', $settings))->execute(Kit::context(Kit::endpoint('detail'), $response), passed());

    Assert::count(0, $headers);
}, 'VersioningFilter: advertising and Vary can be disabled');


Toolkit::test(function (): void {
    [$response] = Kit::response();

    $rejection = new class implements RejectionFactory {
        public function create(ClientVersion $version, int $required): IResult {
            return new ClientOutdated(['upgrade' => "to {$required}"], 410);
        }
    };

    $result = versioningFilter(Kit::version('1', Kit::settings(3, 2)), $rejection)
        ->execute(Kit::context(Kit::endpoint('detail'), $response), passed());

    Assert::same(410, $result->getCode());
    Assert::same(['upgrade' => 'to 2'], $result->getData());
}, 'VersioningFilter: custom RejectionFactory');


/// upgrade

Toolkit::test(function (): void {
    [$response] = Kit::response();
    $endpoint = Kit::endpoint('createAuthor', 'POST', '/books/authors');

    $result = versioningFilter(Kit::version('1'))
        ->execute(Kit::context($endpoint, $response, [ 'nick' => 'Karel Capek' ], 'POST'), passed());

    $parameters = $result->getData();

    Assert::same('Karel', $parameters['firstName']);
    Assert::same('Capek', $parameters['lastName']);
    Assert::false(isset($parameters['nick']));
    Assert::false(isset($parameters['name']));

    // internal parameters are kept untouched
    Assert::same($endpoint, $parameters['_endpoint']);
    Assert::same('createAuthor', $parameters['action']);
}, 'VersioningFilter: input is upgraded in ascending order (1 -> 2 -> 3)');


Toolkit::test(function (): void {
    [$response] = Kit::response();
    $endpoint = Kit::endpoint('createAuthor', 'POST', '/books/authors');

    $result = versioningFilter(Kit::version('2'))
        ->execute(Kit::context($endpoint, $response, [ 'name' => 'Karel Capek', 'nick' => 'kc' ], 'POST'), passed());

    $parameters = $result->getData();
    Assert::same('Karel', $parameters['firstName']);
    Assert::same('kc', $parameters['nick']);   // adapter before 2 not applied for version 2
}, 'VersioningFilter: only adapters above the client version are applied');


Toolkit::test(function (): void {
    [$response] = Kit::response();
    $endpoint = Kit::endpoint('createAuthor', 'POST', '/books/authors');
    $input = [ 'firstName' => 'Karel', 'lastName' => 'Capek' ];

    $result = versioningFilter(Kit::version('3'))
        ->execute(Kit::context($endpoint, $response, $input, 'POST'), passed());

    Assert::same('Karel', $result->getData()['firstName']);
    Assert::same('Capek', $result->getData()['lastName']);
}, 'VersioningFilter: current version input is unchanged');
