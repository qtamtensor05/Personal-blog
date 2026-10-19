<?php

declare(strict_types=1);

namespace App\Application\Content\DTOs;

final readonly class HomeContent
{
    /**
     * @param  list<PublicPostSummary>  $featuredPosts
     * @param  list<PublicPostSummary>  $recentPosts
     */
    public function __construct(
        public array $featuredPosts,
        public array $recentPosts,
    ) {}
}
