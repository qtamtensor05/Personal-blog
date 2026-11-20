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
use App\Application\Content\DTOs\PublicPostSummary;

final readonly class PostEngagementService implements PostEngagementUseCase
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

        return $this->forVisiblePosts($posts);
    }

    /**
     * Counts only the posts already selected by a visibility-aware public use case.
     *
     * @param  list<PostRecord|PublicPostSummary>  $posts
     * @return array<int, PostEngagement>
     */
    public function forVisiblePosts(array $posts): array
    {
        return $this->interactions->engagementForPosts(array_map(
            static fn (PostRecord|PublicPostSummary $post): int => $post->id,
            $posts,
        ));
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
