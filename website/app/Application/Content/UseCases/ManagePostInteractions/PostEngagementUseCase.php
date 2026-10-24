<?php

declare(strict_types=1);

namespace App\Application\Content\UseCases\ManagePostInteractions;

use App\Application\Content\DTOs\PostEngagement;
use App\Application\Content\DTOs\PostLikerPage;
use App\Application\Content\DTOs\PostRecord;
use App\Application\Content\DTOs\PublicPostSummary;

interface PostEngagementUseCase
{
    /**
     * @param  list<PostRecord>  $posts
     * @return array<int, PostEngagement>
     */
    public function forOwnedPosts(int $ownerId, int $blogId, array $posts): array;

    /**
     * @param  list<PostRecord|PublicPostSummary>  $posts
     * @return array<int, PostEngagement>
     */
    public function forVisiblePosts(array $posts): array;

    public function likers(int $ownerId, int $blogId, int $postId, int $page): PostLikerPage;
}
