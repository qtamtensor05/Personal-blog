<?php

declare(strict_types=1);

namespace App\Application\Content\UseCases\ManagePostInteractions;

use App\Application\BlogDesign\Services\OwnedBlogGuard;
use App\Application\Blogs\Exceptions\BlogNotFound;
use App\Application\Content\Contracts\PostInteractionRepository;
use App\Application\Content\Contracts\PostRepository;
use App\Application\Content\DTOs\PostEngagement;
use App\Application\Content\DTOs\PostLikerPage;
use App\Application\Content\DTOs\PostRecord;

final readonly class PostEngagementService
{
    public function __construct(
        private OwnedBlogGuard $guard,
        private PostRepository $posts,
        private PostInteractionRepository $interactions,
    ) {}

    /** @param list<PostRecord> $posts
     * @return array<int, PostEngagement>
     */
    public function forOwnedPosts(int $ownerId, int $blogId, array $posts): array
    {
        $this->guard->ensure($ownerId, $blogId);

        return $this->interactions->engagementForPosts(array_map(fn ($post) => $post->id, $posts));
    }

    public function likers(int $ownerId, int $blogId, int $postId, int $page): PostLikerPage
    {
        $this->guard->ensure($ownerId, $blogId);
        if ($this->posts->find($blogId, $postId) === null) {
            throw new BlogNotFound;
        }

        return $this->interactions->likers($postId, $page, 20);
    }
}
