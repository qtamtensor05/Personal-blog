<?php

declare(strict_types=1);

namespace App\Application\Content\DTOs;

final readonly class PostInteractionState
{
    public function __construct(
        public bool $liked,
        public bool $bookmarked,
    ) {}
}
