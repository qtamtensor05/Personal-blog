<?php

declare(strict_types=1);

namespace App\Application\Content\DTOs;

final readonly class TagRecord
{
    public function __construct(
        public int $id,
        public int $blogId,
        public string $name,
        public string $slug,
    ) {}
}
