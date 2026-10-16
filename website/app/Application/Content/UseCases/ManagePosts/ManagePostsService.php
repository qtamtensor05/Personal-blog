<?php

declare(strict_types=1);

namespace App\Application\Content\UseCases\ManagePosts;

use App\Application\BlogDesign\Services\OwnedBlogGuard;
use App\Application\Blogs\Contracts\BlogSettingsRepository;
use App\Application\Content\Contracts\PostRepository;
use App\Application\Content\Contracts\TaxonomyRepository;
use App\Application\Content\DTOs\PostRecord;
use App\Application\Media\Contracts\MediaAssetRepository;
use App\Domain\Content\Enums\PostStatus;
use App\Domain\Content\ValueObjects\PostDraft;
use DomainException;

/**
 * Posts of a blog. Owner-only (schema has no collaborators).
 */
final readonly class ManagePostsService
{
    public function __construct(
        private OwnedBlogGuard $guard,
        private PostRepository $posts,
        private TaxonomyRepository $taxonomy,
        private MediaAssetRepository $media,
        private BlogSettingsRepository $settings,
    ) {}

    public function create(int $actorId, int $blogId, string $slug, PostDraft $draft): int
    {
        $this->guard->ensure($actorId, $blogId);

        $limit = $this->settings->find($blogId)?->postLimit;

        if ($limit !== null && $this->posts->countForBlog($blogId) >= $limit) {
            throw new DomainException('This blog has reached its post limit.');
        }

        $this->ensureSlugFree($blogId, $slug);
        $this->ensureContentMedia($blogId, $draft);

        return $this->posts->create($blogId, $actorId, $slug, $draft);
    }

    public function saveDraft(int $actorId, int $blogId, int $postId, PostDraft $draft): void
    {
        $this->requirePost($actorId, $blogId, $postId);
        $this->ensureContentMedia($blogId, $draft);

        $this->posts->saveDraft($blogId, $postId, $draft, $actorId);
    }

    /**
     * @param  list<int>  $categoryIds
     * @param  list<int>  $tagIds
     */
    public function updateMeta(
        int $actorId,
        int $blogId,
        int $postId,
        string $slug,
        ?int $coverMediaId,
        ?int $ogMediaId,
        ?string $seoTitle,
        ?string $seoDescription,
        bool $isFeatured,
        array $categoryIds,
        array $tagIds,
    ): void {
        $this->requirePost($actorId, $blogId, $postId);
        $this->ensureSlugFree($blogId, $slug, $postId);

        foreach (['cover image' => $coverMediaId, 'social share image' => $ogMediaId] as $label => $id) {
            if ($id !== null && ! $this->media->isUsableForBlog($id, $blogId)) {
                throw new DomainException("The selected {$label} is not an image of this blog.");
            }
        }

        $categoryIds = array_values(array_unique($categoryIds));
        $tagIds = array_values(array_unique($tagIds));

        foreach ($categoryIds as $id) {
            if ($this->taxonomy->findCategory($blogId, $id) === null) {
                throw new DomainException('A selected category does not belong to this blog.');
            }
        }

        foreach ($tagIds as $id) {
            if ($this->taxonomy->findTag($blogId, $id) === null) {
                throw new DomainException('A selected tag does not belong to this blog.');
            }
        }

        $this->posts->updateMeta(
            $blogId, $postId, $slug, $coverMediaId, $ogMediaId, $seoTitle, $seoDescription,
            $isFeatured, $categoryIds, $tagIds, $actorId,
        );
    }

    public function publish(int $actorId, int $blogId, int $postId): void
    {
        $post = $this->requirePost($actorId, $blogId, $postId);

        // Re-check images at publish time: media may have been deleted since the draft was saved.
        $this->ensureContentMedia($blogId, $post->editable());

        $this->posts->publish($blogId, $postId, $actorId);
    }

    /**
     * Unpublish (back to DRAFT) or archive. SCHEDULED is not offered in the MVP.
     */
    public function changeStatus(int $actorId, int $blogId, int $postId, PostStatus $status): void
    {
        $this->requirePost($actorId, $blogId, $postId);

        if (! in_array($status, [PostStatus::Draft, PostStatus::Archived], true)) {
            throw new DomainException('Use publish to make a post public.');
        }

        $this->posts->setStatus($blogId, $postId, $status, $actorId);
    }

    public function delete(int $actorId, int $blogId, int $postId): void
    {
        $this->requirePost($actorId, $blogId, $postId);
        $this->posts->softDelete($blogId, $postId, $actorId);
    }

    private function requirePost(int $actorId, int $blogId, int $postId): PostRecord
    {
        $this->guard->ensure($actorId, $blogId);
        $post = $this->posts->find($blogId, $postId);

        if ($post === null) {
            throw new DomainException('Post not found.');
        }

        return $post;
    }

    private function ensureSlugFree(int $blogId, string $slug, ?int $exceptId = null): void
    {
        if ($this->posts->slugExists($blogId, $slug, $exceptId)) {
            throw new DomainException('A post with this slug already exists in this blog.');
        }
    }

    /**
     * Image nodes must reference READY images of the same blog.
     */
    private function ensureContentMedia(int $blogId, PostDraft $draft): void
    {
        $ids = $draft->content->mediaIds();
        $found = $this->media->readyAssetsForBlog($blogId, $ids);

        if (count($found) !== count($ids)) {
            throw new DomainException('The content contains an image that is not available in this blog.');
        }
    }
}
