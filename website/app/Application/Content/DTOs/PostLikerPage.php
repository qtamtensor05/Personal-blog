<?php

declare(strict_types=1);

namespace App\Application\Content\DTOs;

final readonly class PostLikerPage
{
    /** @param list<PostLiker> $likers */
    public function __construct(public array $likers, public int $total, public int $page, public int $perPage) {}

    public function lastPage(): int
    {
        return max(1, (int) ceil($this->total / $this->perPage));
    }
}
