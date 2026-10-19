<?php

declare(strict_types=1);

namespace App\Application\Content\UseCases\GetHomeContent;

use App\Application\Content\Contracts\PublicPostCatalog;
use App\Application\Content\DTOs\HomeContent;

final readonly class GetHomeContentService implements GetHomeContentUseCase
{
    private const FEATURED_LIMIT = 3;

    private const RECENT_LIMIT = 6;

    public function __construct(private PublicPostCatalog $posts) {}

    public function execute(): HomeContent
    {
        $featured = $this->posts->featured(self::FEATURED_LIMIT);

        return new HomeContent(
            featuredPosts: $featured,
            recentPosts: $this->posts->recent(self::RECENT_LIMIT),
        );
    }
}
