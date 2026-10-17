<?php

declare(strict_types=1);

namespace App\Application\Content\DTOs;

final readonly class PublicPostSummary
{
    public function __construct(
        public int $id,
        public string $title,
        public string $slug,
        public string $blogSlug,
        public ?string $excerpt,
        public ?string $coverUrl,
        public ?string $publishedAt,
        public string $authorName,
        public bool $isFeatured,
    ) {}
}
