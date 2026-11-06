<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Tests\Fixtures\Versioning\AuthorResponse;
use Tests\Fixtures\Versioning\BookInitialsDowngrader;
use Tests\Fixtures\Versioning\BookRenameDowngrader;
use Tests\Fixtures\Versioning\BookResponse;
use Tests\Fixtures\Versioning\Kit;
use Varhall\Restino\Filters\Expand;
use Varhall\Restino\Results\CollectionResult;
use Varhall\Restino\Results\IExpandable;
use Varhall\Restino\Results\Serializer;
use Varhall\Restino\Results\SimpleResult;
use Varhall\Restino\Versioning\Attributes\Downgrade;
use Varhall\Restino\Versioning\ClientVersion;
use Varhall\Restino\Versioning\Compatibility;
use Varhall\Restino\Versioning\Downgrader;
use Varhall\Restino\Versioning\HeaderVersionResolver;
use Varhall\Utilino\Collections\ArrayCollection;
use Varhall\Utilino\ISerializable;


/// fixtures

class ShelfMarkDowngrader implements Downgrader
{
    public static array $seen = [];

    public function __invoke(array $data, mixed $source): array
    {
        self::$seen[] = array_keys($data);
        $data['mark'] = strtoupper($data['mark']);
        return $data;
    }
}

#[Downgrade(before: 3, with: ShelfMarkDowngrader::class)]
class ShelfResponse implements ISerializable, IExpandable
{
    public function toArray(): array
    {
        return [ 'mark' => 'a1' ];
    }

    public function expansions(): array
    {
        return [ 'author' => fn() => new AuthorResponse('Karel', 'Capek') ];
    }
}

class WrongAdapter {}

#[Downgrade(before: 3, with: WrongAdapter::class)]
class WrongResponse implements ISerializable
{
    public function toArray(): array
    {
        return [];
    }
}

/**
 * Parent adapter which modifies its child - the child becomes plain data
 */
class ChildModifyingDowngrader implements Downgrader
{
    public function __invoke(array $data, mixed $source): array
    {
        $data['author']['firstName'] = strtoupper($data['author']['firstName']);
        return $data;
    }
}

#[Downgrade(before: 3, with: ChildModifyingDowngrader::class)]
class ModifyingResponse implements ISerializable
{
    public function toArray(): array
    {
        return [ 'author' => new AuthorResponse('Karel', 'Capek') ];
    }
}


function serializer(?string $version): Serializer
{
    return new Serializer(new Compatibility(Kit::version($version)));
}

function book(): BookResponse
{
    return new BookResponse('Book 7', new AuthorResponse('Karel', 'Capek'));
}


/// step-by-step downgrade

Toolkit::test(function (): void {
    Assert::same(
        [ 'title' => 'Book 7', 'author' => [ 'firstName' => 'Karel', 'lastName' => 'Capek' ] ],
        serializer('3')->serialize(book())
    );
}, 'Downgrade: current version is not converted');


Toolkit::test(function (): void {
    Assert::same(
        [ 'title' => 'Book 7', 'author' => [ 'name' => 'Karel Capek' ], 'initials' => 'KC' ],
        serializer('2')->serialize(book())
    );
}, 'Downgrade: one step');


Toolkit::test(function (): void {
    BookInitialsDowngrader::$seen = [];
    BookRenameDowngrader::$seen = [];

    Assert::same(
        [ 'name' => 'Book 7', 'author' => [ 'fullName' => 'Karel Capek' ], 'initials' => 'KC' ],
        serializer('1')->serialize(book())
    );

    // each parent adapter sees the child in the shape of its own version
    Assert::same([ 'firstName,lastName' ], BookInitialsDowngrader::$seen);
    Assert::same([ 'name' ], BookRenameDowngrader::$seen);
}, 'Downgrade: more steps, parent adapters see children in the shape of their version');


Toolkit::test(function (): void {
    $books = [ book(), new BookResponse('Book 8', new AuthorResponse('Jan', 'Neruda')) ];

    $result = new SimpleResult($books);
    $result->setSerializer(serializer('2'));
    $data = $result->execute(new \Nette\Http\Response(), Kit::httpRequest());

    Assert::same([ 'name' => 'Jan Neruda' ], $data[1]['author']);
    Assert::same('JN', $data[1]['initials']);
    Assert::count(2, $data);
}, 'Downgrade: list result');


Toolkit::test(function (): void {
    $result = new CollectionResult(new ArrayCollection([ book() ]));
    $result->setSerializer(serializer('1'));

    $data = $result->execute(new \Nette\Http\Response(), Kit::httpRequest());

    Assert::same('Book 7', $data[0]['name']);

    // serializer survives conversion of the result
    $copy = CollectionResult::fromResult($result);
    Assert::same($result->getSerializer(), $copy->getSerializer());
}, 'Downgrade: collection result and fromResult()');


Toolkit::test(function (): void {
    $compatibility = new Compatibility(new ClientVersion(Kit::settings(3, 1), new HeaderVersionResolver()));

    Assert::false($compatibility->isActive());
    Assert::same(
        (new Serializer())->serialize(book()),
        (new Serializer($compatibility))->serialize(book())
    );
}, 'Downgrade: unresolved client version (CLI, queues) does not convert anything');


Toolkit::test(function (): void {
    // AuthorResponse adapter is not applied any more - the child must be converted by its own adapter only
    Assert::same(
        [ 'author' => [ 'firstName' => 'KAREL', 'lastName' => 'Capek' ] ],
        serializer('2')->serialize(new ModifyingResponse())
    );
}, 'Downgrade: child modified by parent adapter is plain data afterwards');


Toolkit::test(function (): void {
    Assert::exception(
        fn() => serializer('2')->serialize(new WrongResponse()),
        \Nette\InvalidStateException::class,
        '%a%must implement%a%'
    );
}, 'Downgrade: adapter must implement Downgrader');


/// expand

Toolkit::test(function (): void {
    ShelfMarkDowngrader::$seen = [];

    $result = new SimpleResult(new ShelfResponse());
    $result->setSerializer(serializer('2'));

    $context = Kit::context(Kit::endpoint('detail'), Kit::response()[0], [ Expand::QUERY_PARAMETER => 'author' ]);
    (new Expand())->execute($context, fn() => $result);

    Assert::same(
        [ 'mark' => 'A1', 'author' => [ 'name' => 'Karel Capek' ] ],
        $result->execute(new \Nette\Http\Response(), Kit::httpRequest())
    );

    // adapter of the expanded object does not see expanded keys
    Assert::same([ [ 'mark' ] ], ShelfMarkDowngrader::$seen);
}, 'Downgrade: expanded relations are downgraded, owner adapter does not see them');


Toolkit::test(function (): void {
    $result = new SimpleResult(new ShelfResponse());

    $context = Kit::context(Kit::endpoint('detail'), Kit::response()[0], [ Expand::QUERY_PARAMETER => 'author' ]);
    (new Expand())->execute($context, fn() => $result);

    Assert::same(
        [ 'mark' => 'a1', 'author' => [ 'firstName' => 'Karel', 'lastName' => 'Capek' ] ],
        $result->execute(new \Nette\Http\Response(), Kit::httpRequest())
    );
}, 'Downgrade: expand without versioning is unchanged');


/// action output

Toolkit::test(function (): void {
    $endpoint = Kit::endpoint('stats', 'GET', '/books/stats');

    $old = new Compatibility(Kit::version('2'));
    $new = new Compatibility(Kit::version('3'));
    $payload = [ 'total' => 10, 'active' => 7 ];

    Assert::same([ 'count' => 10 ], $old->downgradeAction($endpoint, $payload));
    Assert::same($payload, $new->downgradeAction($endpoint, $payload));
    Assert::same('scalar', $old->downgradeAction($endpoint, 'scalar'));
}, 'Downgrade: action output');
