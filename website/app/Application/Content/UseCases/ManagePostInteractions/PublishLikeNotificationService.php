<?php

declare(strict_types=1);

namespace App\Application\Content\UseCases\ManagePostInteractions;

use App\Application\Blogs\Exceptions\BlogNotFound;
use App\Application\Content\Contracts\LikeNotificationCooldownRepository;
use App\Application\Content\Contracts\PostInteractionRepository;
use App\Application\Content\Contracts\PostLocationRepository;
use App\Application\Content\UseCases\ViewPublicPost\ViewPublicPostService;
use App\Application\Notifications\Contracts\NotificationPublisher;

final readonly class PublishLikeNotificationService implements PublishLikeNotificationUseCase
{
    public function __construct(
        private ViewPublicPostService $posts,
        private PostInteractionRepository $interactions,
        private PostLocationRepository $locations,
        private LikeNotificationCooldownRepository $cooldowns,
        private NotificationPublisher $notifications,
    ) {}

    public function execute(int $likerId, int $postId, int $ownerId): void
    {
        if ($likerId === $ownerId || ! $this->interactions->isLiked($likerId, $postId)) {
            return;
        }

        $location = $this->locations->findForPost($postId);
        if ($location === null) {
            return;
        }

        try {
            $result = $this->posts->execute($location->blogSlug, $location->postSlug, $likerId);
        } catch (BlogNotFound) {
            return;
        }

        if ($result['post']->id !== $postId || $result['page']->blog->ownerUserId !== $ownerId) {
            return;
        }

        $name = $this->interactions->likerName($likerId);
        if ($name === null) {
            return;
        }

        $this->cooldowns->publishIfDue($likerId, $postId, $ownerId, function () use ($likerId, $postId, $ownerId, $name): bool {
            // A queued job can wait while a reader unlikes or the post is hidden.
            if (! $this->interactions->isLiked($likerId, $postId)) {
                return false;
            }
            $location = $this->locations->findForPost($postId);
            if ($location === null) {
                return false;
            }
            try {
                $current = $this->posts->execute($location->blogSlug, $location->postSlug, $likerId);
            } catch (BlogNotFound) {
                return false;
            }
            if ($current['post']->id !== $postId || $current['page']->blog->ownerUserId !== $ownerId) {
                return false;
            }
            $this->notifications->publish($ownerId, 'post.liked', [
                'title' => 'Bài viết mới được thích',
                'message' => $name.' đã thích bài viết "'.$current['post']->title.'".',
                'liker_user_id' => $likerId,
                'liker_name' => $name,
                'blog_id' => $location->blogId,
                'post_id' => $postId,
            ]);

            return true;
        });
    }
}
