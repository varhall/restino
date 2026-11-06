<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Varhall\Restino\Filters\Chain;
use Varhall\Restino\Filters\Configuration;
use Varhall\Restino\Filters\Context;
use Varhall\Restino\Filters\Cors;
use Varhall\Restino\Filters\IFilter;
use Varhall\Restino\Results\IResult;
use Varhall\Restino\Results\SimpleResult;


/**
 * Filter which records the order of execution
 */
class RecordingFilter implements IFilter
{
    public static array $log = [];

    public function __construct(public string $name) {}

    public function execute(Context $context, callable $next): IResult
    {
        self::$log[] = $this->name;
        return $next($context);
    }
}

class RecordingCors extends Cors
{
    public function execute(Context $context, callable $next): IResult
    {
        RecordingFilter::$log[] = 'cors';
        return $next($context);
    }
}

function runChain(Chain $chain): array
{
    RecordingFilter::$log = [];
    $chain->build(fn() => new SimpleResult(null), 'action')(mock(Context::class));

    return RecordingFilter::$log;
}


Toolkit::test(function (): void {
    $chain = new Chain();
    $chain->add('a', new RecordingFilter('a'));
    $chain->add('b', new RecordingFilter('b'));
    $chain->add('c', new RecordingFilter('c'));

    Assert::same(['a', 'b', 'c'], runChain($chain));
    Assert::same(['a', 'b', 'c'], array_keys($chain->getOrderedFilters()));
}, 'Chain order: registration order without constraints');


Toolkit::test(function (): void {
    $chain = new Chain();
    $chain->add('versioning', new RecordingFilter('versioning'))->after('log');
    $chain->add('auth', new RecordingFilter('auth'));
    $chain->add('log', new RecordingFilter('log'));

    Assert::same(['auth', 'log', 'versioning'], runChain($chain));
}, 'Chain order: after() by filter name moves filter behind the referenced one');


Toolkit::test(function (): void {
    $chain = new Chain();
    $chain->add('a', new RecordingFilter('a'));
    $chain->add('b', new RecordingFilter('b'));
    $chain->add('first', new RecordingFilter('first'))->before('a');

    Assert::same(['first', 'a', 'b'], runChain($chain));
}, 'Chain order: before() by filter name');


Toolkit::test(function (): void {
    $chain = new Chain();
    $chain->add('versioning', new RecordingFilter('versioning'))->after(Cors::class);
    $chain->add('auth', new RecordingFilter('auth'));

    // Cors registered later (e.g. in controller setup) under arbitrary name
    $chain->add('my-cors', new RecordingCors());

    Assert::same(['auth', 'cors', 'versioning'], runChain($chain));
}, 'Chain order: after() by class matches subclasses registered later');


Toolkit::test(function (): void {
    $chain = new Chain();
    $chain->add('versioning', new RecordingFilter('versioning'))->after(Cors::class, 'missing');
    $chain->add('auth', new RecordingFilter('auth'));

    Assert::same(['versioning', 'auth'], runChain($chain));
}, 'Chain order: missing references are ignored');


Toolkit::test(function (): void {
    $chain = new Chain();
    $chain->add('a', new RecordingFilter('a'))->after('b');
    $chain->add('b', new RecordingFilter('b'))->after('a');

    Assert::exception(fn() => $chain->getOrderedFilters(), \Nette\InvalidStateException::class, '%a%cyclic%a%');
}, 'Chain order: cycle is reported');


Toolkit::test(function (): void {
    $config = new Configuration(new RecordingFilter('x'));
    $config->after('a', ['b', 'c'])->after('a')->before([Cors::class]);

    Assert::same(['a', 'b', 'c'], $config->getAfter());
    Assert::same([Cors::class], $config->getBefore());
}, 'Configuration: after() and before() accept lists and remove duplicates');


Toolkit::test(function (): void {
    $chain = new Chain();
    $chain->add('a', new RecordingFilter('a'));

    Assert::true($chain->has('a'));
    Assert::false($chain->has('b'));
}, 'Chain: has()');
