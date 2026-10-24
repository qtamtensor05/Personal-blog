<?php

declare(strict_types=1);

namespace App\Application\Content\UseCases\ManagePostInteractions;

use App\Application\Blogs\Exceptions\BlogNotFound;
use App\Application\Content\Contracts\PostLocationRepository;
use App\Application\Content\DTOs\PostLocation;
use App\Application\Content\UseCases\ViewPublicPost\ViewPublicPostService;
use App\Application\Notifications\DTOs\NotificationRecord;

final readonly class ResolveLikedPostLocationsService implements ResolveLikedPostLocationsUseCase
{
    public function __construct(
        private PostLocationRepository $locations,
        private ViewPublicPostService $posts,
    ) {}

    public function execute(array $notifications, int $recipientUserId): array
    {
        $links = [];

        foreach ($notifications as $notification) {
            $location = $this->visibleLocation($notification, $recipientUserId);
            if ($location !== null) {
                $links[$notification->id] = $location;
            }
        }

        return $links;
    }

    private function visibleLocation(NotificationRecord $notification, int $recipientUserId): ?PostLocation
    {
        $data = $notification->data;
        if ($notification->type !== 'post.liked') {
            return null;
        }

        $viewerId = isset($data['liker_user_id']) ? (int) $data['liker_user_id'] : null;
        $postId = isset($data['post_id']) ? (int) $data['post_id'] : null;
        $location = $postId !== null ? $this->locations->findForPost($postId) : null;

        // Legacy notifications contain only slugs; retain their links while those slugs still resolve.
        if ($postId === null && isset($data['blog_slug'], $data['post_slug'])) {
            try {
                $page = $this->posts->execute((string) $data['blog_slug'], (string) $data['post_slug'], $viewerId);
                $location = new PostLocation($page['page']->blog->id, $page['page']->blog->slug, $page['post']->slug);
            } catch (BlogNotFound) {
                return null;
            }
        }

        if ($location === null || (isset($data['blog_id']) && $location->blogId !== (int) $data['blog_id'])) {
            return null;
        }

        try {
            $page = $this->posts->execute($location->blogSlug, $location->postSlug, $viewerId);
        } catch (BlogNotFound) {
            return null;
        }

        if ($page['page']->blog->ownerUserId !== $recipientUserId || ($postId !== null && $page['post']->id !== $postId)) {
            return null;
        }

        return $location;
    }
}
