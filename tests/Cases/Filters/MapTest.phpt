<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Varhall\Restino\Filters\Context;
use Varhall\Restino\Filters\Map;
use Varhall\Restino\Results\AbstractResult;
use Varhall\Restino\Results\IResult;


class DummyTarget {
    public mixed $source;

    public function __construct(mixed $source) {
        $this->source = $source;
    }
}


Toolkit::test(function (): void {
    $context = mock(Context::class);
    $result = mock(AbstractResult::class);

    $map = new Map(DummyTarget::class);

    $result->shouldReceive('addMapper')
        ->once()
        ->with(Mockery::on(function ($mapper) {
            $input = ['foo' => 'bar'];
            $output = $mapper($input);

            return $output instanceof DummyTarget
                && $output->source === $input;
        }));

    $returned = $map->execute($context, fn() => $result);

    Assert::same($result, $returned);
}, 'Map filter applies mapper on AbstractResult');

Toolkit::test(function (): void {
    $context = mock(Context::class);
    $result = mock(IResult::class); // not AbstractResult

    $map = new Map(DummyTarget::class);

    $returned = $map->execute($context, fn() => $result);

    Assert::same($result, $returned);
}, 'Map filter skips non-AbstractResult results');