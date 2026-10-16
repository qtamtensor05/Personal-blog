<?php

declare(strict_types=1);

namespace App\Application\Blogs\DTOs;

use App\Domain\Blogs\Enums\BlogStatus;
use App\Domain\Blogs\Enums\BlogVisibility;

final readonly class BlogRecord
{
    public function __construct(
        public int $id,
        public int $ownerUserId,
        public string $name,
        public string $slug,
        public ?string $description,
        public BlogStatus $status,
        public BlogVisibility $visibility,
        public bool $discoverable,
        public ?string $publishedAt,
        public ?string $deletedAt,
    ) {}
}
