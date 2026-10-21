<?php

declare(strict_types=1);

namespace App\Application\Content\UseCases\ManagePostInteractions;

use App\Application\Blogs\DTOs\BlogRecord;
use App\Application\Blogs\Exceptions\BlogNotFound;
use App\Application\Content\Contracts\PostInteractionRepository;
use App\Application\Content\DTOs\PostInteractionState;
use App\Application\Content\DTOs\PostRecord;
use App\Application\Content\UseCases\ViewPublicPost\ViewPublicPostService;

final readonly class PostInteractionsService
{
    public function __construct(
        private ViewPublicPostService $posts,
        private PostInteractionRepository $interactions,
    ) {}

    public function state(int $userId, int $postId): PostInteractionState
    {
        return $this->interactions->state($userId, $postId);
    }

    public function setLike(int $userId, string $blogSlug, string $postSlug, bool $liked): void
    {
        $post = $this->posts->execute($blogSlug, $postSlug, $userId)['post'];
        $this->interactions->setLike($userId, $post->id, $liked);
    }

    public function setBookmark(int $userId, string $blogSlug, string $postSlug, bool $bookmarked): void
    {
        $post = $this->posts->execute($blogSlug, $postSlug, $userId)['post'];
        $this->interactions->setBookmark($userId, $post->id, $bookmarked);
    }

    /**
     * Recheck each saved post through the real public-post use case. This keeps
     * unlisted and owner-preview rules consistent with the post page.
     *
     * @return list<array{blog: BlogRecord, post: PostRecord}>
     */
    public function bookmarks(int $userId): array
    {
        $visible = [];

        foreach ($this->interactions->bookmarkedPosts($userId) as $reference) {
            try {
                $page = $this->posts->execute($reference->blogSlug, $reference->postSlug, $userId);
            } catch (BlogNotFound) {
                continue;
            }

            $visible[] = ['blog' => $page['page']->blog, 'post' => $page['post']];
        }

        return $visible;
    }
}
