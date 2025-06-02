<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Varhall\Restino\Results\Pagination;


Toolkit::test(function (): void {
    $pagination = new Pagination(limit: 10, offset: 20, total: 100);

    Assert::same(10, $pagination->getLimit(), 'getLimit');
    Assert::same(20, $pagination->getOffset(), 'getOffset');
    Assert::same(100, $pagination->getTotal(), 'getTotal');

    Assert::same(30, $pagination->getNextOffset(), 'getNextOffset');
    Assert::same(10, $pagination->getPreviousOffset(), 'getPreviousOffset');

    Assert::same([
        'limit'  => 10,
        'offset' => [
            'current'  => 20,
            'next'     => 30,
            'previous' => 10,
        ],
        'total' => 100
    ], $pagination->toArray(), 'toArray');
}, 'Standard offset in the middle of dataset');


Toolkit::test(function (): void {
    $pagination = new Pagination(limit: 25, offset: 75, total: 100);

    Assert::same(null, $pagination->getNextOffset(), 'getNextOffset (at end)');
    Assert::same(50, $pagination->getPreviousOffset(), 'getPreviousOffset (at end)');
}, 'Offset at end of dataset');


Toolkit::test(function (): void {
    $pagination = new Pagination(limit: 25, offset: 0, total: 100);

    Assert::same(25, $pagination->getNextOffset(), 'getNextOffset (at start)');
    Assert::same(null, $pagination->getPreviousOffset(), 'getPreviousOffset (at start)');
}, 'Offset at start of dataset');


Toolkit::test(function (): void {
    $pagination = new Pagination(limit: 100, offset: 0, total: 100);

    Assert::same(null, $pagination->getNextOffset(), 'getNextOffset (full single page)');
    Assert::same(null, $pagination->getPreviousOffset(), 'getPreviousOffset (full single page)');
}, 'Only one page');


Toolkit::test(function (): void {
    $pagination = new Pagination(limit: 50, offset: 100, total: 100);

    Assert::same(null, $pagination->getNextOffset(), 'getNextOffset (offset beyond total)');
    Assert::same(50, $pagination->getPreviousOffset(), 'getPreviousOffset (offset beyond total)');
}, 'Offset at total edge');

