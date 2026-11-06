<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Tests\Fixtures\Versioning\AuthorResponse;
use Tests\Fixtures\Versioning\BookResponse;
use Tests\Fixtures\Versioning\BooksController;
use Tests\Fixtures\Versioning\Kit;
use Tests\Fixtures\Versioning\SplitNameUpgrader;
use Tests\Fixtures\Versioning\StatsDowngrader;
use Varhall\Restino\Controllers\Attributes\Get;
use Varhall\Restino\Controllers\Attributes\Path;
use Varhall\Restino\Controllers\IController;
use Varhall\Restino\Versioning\Attributes\Downgrade;
use Varhall\Restino\Versioning\Attributes\MinVersion;
use Varhall\Restino\Versioning\Attributes\Upgrade;
use Varhall\Restino\Versioning\Inspection\Inventory;
use Varhall\Restino\Versioning\Inspection\Violation;


/// fixtures

#[Path('/dirty')]
class DirtyController implements IController
{
    #[Get('/dead')]
    #[Upgrade(before: 2, with: SplitNameUpgrader::class)]
    public function deadAdapter() {}

    #[Get('/duplicate')]
    #[Upgrade(before: 3, with: SplitNameUpgrader::class)]
    #[Upgrade(before: 3, with: SplitNameUpgrader::class)]
    public function duplicateAdapter() {}

    #[Get('/future')]
    #[Upgrade(before: 4, with: SplitNameUpgrader::class)]
    public function futureAdapter() {}

    #[Get('/wrong')]
    #[Upgrade(before: 3, with: StatsDowngrader::class)]
    public function wrongAdapter() {}

    #[Get('/redundant-min')]
    #[MinVersion(2)]
    public function redundantMinimum() {}

    #[Get('/future-min')]
    #[MinVersion(4)]
    public function futureMinimum() {}

    #[Get('/old', before: 2)]
    public function unreachable() {}

    #[Get('/new', since: 2)]
    public function redundantSince() {}

    #[Get('/next', since: 4)]
    public function futureSince() {}

    #[Get('/book')]
    #[Downgrade(before: 3, with: StatsDowngrader::class)]
    public function conflictingDowngrade(): BookResponse {}
}


/// tests

Toolkit::test(function (): void {
    $inventory = new Inventory(Kit::settings(3, 1));

    Assert::same([], $inventory->check([ BooksController::class, BookResponse::class, AuthorResponse::class ]));
}, 'Inventory: clean code has no violations');


Toolkit::test(function (): void {
    $inventory = new Inventory(Kit::settings(3, 2));
    $violations = $inventory->check([ new DirtyController() ]);

    $messages = array_map(fn(Violation $v) => (string) $v, $violations);

    $expected = [
        'DirtyController::deadAdapter() #[Upgrade]: adapter before 2 is never applied%a%',
        'DirtyController::futureAdapter() #[Upgrade]: adapter before 4 refers to a future version (current is 3)',
        'DirtyController::wrongAdapter() #[Upgrade]: adapter class %a%StatsDowngrader must implement %a%Upgrader',
        'DirtyController::redundantMinimum() #[MinVersion]: minimum version 2 is redundant%a%',
        'DirtyController::futureMinimum() #[MinVersion]: minimum version 4 refers to a future version%a%',
        'DirtyController::unreachable() #[Get]: endpoint for versions before 2 is unreachable%a%',
        'DirtyController::redundantSince() #[Get]: restriction since 2 is redundant%a%',
        'DirtyController::futureSince() #[Get]: endpoint since 4 refers to a future version%a%',
        'DirtyController::duplicateAdapter() #[Upgrade]: multiple adapters before 3%a%',
        'DirtyController::conflictingDowngrade() #[Downgrade]: action downgrade cannot be combined with response class %a%BookResponse%a%',
    ];

    Assert::count(count($expected), $messages);

    foreach ($expected as $pattern) {
        Assert::true(
            count(array_filter($messages, fn($m) => \Tester\Assert::isMatching($pattern, $m))) === 1,
            "Expected violation: {$pattern}\nGot:\n" . implode("\n", $messages)
        );
    }
}, 'Inventory: dead, redundant, future, duplicate and conflicting constructs');


Toolkit::test(function (): void {
    $inventory = new Inventory(Kit::settings(3, 1));
    $items = $inventory->list([ BooksController::class, BookResponse::class ]);

    $summary = array_map(
        fn($item) => ($item['method'] ?? '-') . ':' . (new \ReflectionClass($item['attribute']))->getShortName() . ':' . ($item['version'] ?? '-'),
        $items
    );

    Assert::same([
        'createAuthor:Upgrade:3',
        'createAuthor:Upgrade:2',
        'stats:Downgrade:3',
        'legacyCatalog:Get:-',
        'catalog:Get:-',
        'recommended:MinVersion:3',
        '-:Downgrade:3',
        '-:Downgrade:2',
    ], $summary);
}, 'Inventory: list of versioning constructs');
