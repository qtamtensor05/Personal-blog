<?php

declare(strict_types=1);

namespace App\Application\Content\DTOs;

final readonly class PublicPostPage
{
    /** @param list<PublicPostSummary> $items */
    public function __construct(
        public array $items,
        public int $total,
        public int $currentPage,
        public int $perPage,
    ) {}

    public function lastPage(): int
    {
        return max(1, (int) ceil($this->total / $this->perPage));
    }
}
