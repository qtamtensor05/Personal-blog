<?php

declare(strict_types=1);

namespace App\Application\Content\DTOs;

use App\Application\Blogs\DTOs\BlogRecord;

final readonly class BookmarkedPostItem
{
    public function __construct(
        public BlogRecord $blog,
        public PostRecord $post,
        public ?string $coverUrl,
        public PostEngagement $engagement,
    ) {}
}
