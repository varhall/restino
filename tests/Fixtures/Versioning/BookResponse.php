<?php

namespace Tests\Fixtures\Versioning;

use Varhall\Restino\Versioning\Attributes\Downgrade;
use Varhall\Utilino\ISerializable;

/**
 * Shape 3: { title, author: Author }
 * Shape 2: { title, initials, author: Author }     initials are computed from author in shape 3
 * Shape 1: { name, initials, author: Author }      author must be in shape 2 here (name)
 */
#[Downgrade(before: 3, with: BookInitialsDowngrader::class)]
#[Downgrade(before: 2, with: BookRenameDowngrader::class)]
class BookResponse implements ISerializable
{
    public function __construct(
        public string $title,
        public AuthorResponse $author,
    ) {
    }

    public function toArray(): array
    {
        return [ "title" => $this->title, "author" => $this->author ];
    }
}
