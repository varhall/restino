<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Tests\Fixtures\Versioning\Kit;
use Varhall\Restino\Versioning\ClientVersion;
use Varhall\Restino\Versioning\HeaderVersionResolver;
use Varhall\Restino\Versioning\Settings;


/// resolver

Toolkit::test(function (): void {
    $resolver = new HeaderVersionResolver('X-Client-Version');

    Assert::null($resolver->resolve(Kit::httpRequest()));
    Assert::same(3, $resolver->resolve(Kit::httpRequest(['X-Client-Version' => '3'])));
    Assert::same(12, $resolver->resolve(Kit::httpRequest(['x-client-version' => ' 12 '])));
    Assert::same(0, $resolver->resolve(Kit::httpRequest(['X-Client-Version' => 'abc'])));
    Assert::same(0, $resolver->resolve(Kit::httpRequest(['X-Client-Version' => '1.2'])));
    Assert::same(0, $resolver->resolve(Kit::httpRequest(['X-Client-Version' => '-1'])));
    Assert::same(0, $resolver->resolve(Kit::httpRequest(['X-Client-Version' => ''])));
    Assert::same(['X-Client-Version'], $resolver->varyHeaders());
}, 'HeaderVersionResolver: missing, valid and invalid values');


/// missing policy

Toolkit::test(function (): void {
    $version = Kit::version(null, Kit::settings(3, 2, Settings::MISSING_REJECT));

    Assert::same(ClientVersion::UNKNOWN, $version->get());
    Assert::null($version->declared());
    Assert::true($version->isResolved());
}, 'ClientVersion: missing header with reject policy is unknown version 0');

Toolkit::test(function (): void {
    Assert::same(2, Kit::version(null, Kit::settings(3, 2, Settings::MISSING_MINIMUM))->get());
    Assert::same(3, Kit::version(null, Kit::settings(3, 2, Settings::MISSING_CURRENT))->get());
}, 'ClientVersion: missing header with minimum and current policy');

Toolkit::test(function (): void {
    $version = Kit::version('nonsense', Kit::settings(3, 2, Settings::MISSING_CURRENT));

    Assert::same(0, $version->get());
    Assert::same(0, $version->declared());
}, 'ClientVersion: invalid value is always unknown version regardless of policy');

Toolkit::test(function (): void {
    $version = Kit::version('2');

    Assert::same(2, $version->get());
    Assert::same(2, $version->declared());
    Assert::true($version->before(3));
    Assert::false($version->before(2));
}, 'ClientVersion: declared version and before()');


/// lifecycle

Toolkit::test(function (): void {
    $version = new ClientVersion(Kit::settings(5, 3), new HeaderVersionResolver());

    Assert::false($version->isResolved());
    Assert::same(5, $version->get());
    Assert::false($version->before(5));
}, 'ClientVersion: before resolve() the current version is used');

Toolkit::test(function (): void {
    $version = new ClientVersion(Kit::settings(5, 3), new HeaderVersionResolver());

    $version->resolve(Kit::httpRequest(['X-Api-Version' => '3']));
    Assert::same(3, $version->get());

    $version->resolve(Kit::httpRequest(['X-Api-Version' => '4']));
    Assert::same(4, $version->get());

    $version->set(2);
    Assert::same(2, $version->get());
    Assert::same(2, $version->declared());

    $version->reset();
    Assert::false($version->isResolved());
    Assert::same(5, $version->get());
    Assert::null($version->declared());
}, 'ClientVersion: resolve() overwrites state, set() and reset()');


/// settings

Toolkit::test(function (): void {
    Assert::exception(fn() => new Settings(3, 0), \Nette\InvalidArgumentException::class);
    Assert::exception(fn() => new Settings(3, 4), \Nette\InvalidArgumentException::class);
    Assert::exception(fn() => new Settings(3, 1, 'sometimes'), \Nette\InvalidArgumentException::class);

    $settings = new Settings(3, 1, advertiseMinimum: null);
    Assert::same(['X-Api-Current-Version'], $settings->advertisedHeaders());
}, 'Settings: validation and advertised headers');
