<?php

declare(strict_types=1);

namespace App\Application\Content\DTOs;

final readonly class BookmarkedPostReference
{
    public function __construct(
        public string $blogSlug,
        public string $postSlug,
    ) {}
}
