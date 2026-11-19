<?php

declare(strict_types=1);

namespace App\Application\Content\DTOs;

final readonly class PostLocation
{
    public function __construct(
        public int $blogId,
        public string $blogSlug,
        public string $postSlug,
    ) {}
}
