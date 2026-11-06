<?php

namespace Tests\Fixtures\Versioning;

use Varhall\Restino\Controllers\Attributes\Get;
use Varhall\Restino\Controllers\Attributes\Path;
use Varhall\Restino\Controllers\Attributes\Post;
use Varhall\Restino\Controllers\IController;
use Varhall\Restino\Versioning\Attributes\Downgrade;
use Varhall\Restino\Versioning\Attributes\MinVersion;
use Varhall\Restino\Versioning\Attributes\Unversioned;
use Varhall\Restino\Versioning\Attributes\Upgrade;

#[Path('/books')]
class BooksController implements IController
{
    #[Post('/authors')]
    #[Upgrade(before: 3, with: SplitNameUpgrader::class)]
    #[Upgrade(before: 2, with: RenameNickUpgrader::class)]
    public function createAuthor(string $firstName, string $lastName): AuthorResponse
    {
        return new AuthorResponse($firstName, $lastName);
    }

    #[Get('/stats')]
    #[Downgrade(before: 3, with: StatsDowngrader::class)]
    public function stats(): array
    {
        return [ 'total' => 10, 'active' => 7 ];
    }

    #[Get('/catalog', before: 2)]
    public function legacyCatalog(): array
    {
        return [ 'catalog' => 'legacy' ];
    }

    #[Get('/catalog', since: 2)]
    public function catalog(): array
    {
        return [ 'catalog' => 'current' ];
    }

    #[Get('/recommended')]
    #[MinVersion(3)]
    public function recommended(): array
    {
        return [ 'recommended' => true ];
    }

    #[Get('/cover')]
    #[Unversioned]
    public function cover(): array
    {
        return [ 'cover' => 'image' ];
    }

    // router takes the first matching endpoint, so the parametrized path is the last one
    #[Get('/{id}')]
    public function detail(int $id): BookResponse
    {
        return new BookResponse("Book {$id}", new AuthorResponse('Karel', 'Capek'));
    }
}
