<?php

declare(strict_types=1);

namespace App\Application\Content\UseCases\ListPublicPosts;

use App\Application\Content\Contracts\PublicPostCatalog;
use App\Application\Content\DTOs\PublicPostPage;

final readonly class ListPublicPostsService
{
    private const PER_PAGE = 9;

    public function __construct(private PublicPostCatalog $posts) {}

    public function execute(?string $search, int $page): PublicPostPage
    {
        $search = trim((string) $search);

        return $this->posts->paginate(
            $search === '' ? null : $search,
            max(1, $page),
            self::PER_PAGE,
        );
    }
}
