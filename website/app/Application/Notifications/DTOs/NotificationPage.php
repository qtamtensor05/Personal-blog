<?php

declare(strict_types=1);

namespace App\Application\Notifications\DTOs;

final readonly class NotificationPage
{
    /** @param list<NotificationRecord> $items */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {}

    public function lastPage(): int
    {
        return max(1, (int) ceil($this->total / $this->perPage));
    }
}
