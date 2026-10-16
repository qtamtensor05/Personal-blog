<?php

declare(strict_types=1);

namespace App\Application\Content\UseCases\ManagePages;

use App\Application\BlogDesign\Services\OwnedBlogGuard;
use App\Application\Content\Contracts\PageRepository;
use App\Application\Content\DTOs\PageRecord;
use App\Application\Media\Contracts\MediaAssetRepository;
use App\Domain\Content\Enums\PostStatus;
use App\Domain\Content\ValueObjects\PostDraft;
use App\Domain\Content\ValueObjects\TiptapDocument;
use DomainException;

/**
 * Static pages of a blog (owner-only). page_key is set from the slug at creation and
 * never changes, so it stays a stable identifier when the slug is edited.
 */
final readonly class ManagePagesService
{
    public function __construct(
        private OwnedBlogGuard $guard,
        private PageRepository $pages,
        private MediaAssetRepository $media,
    ) {}

    public function create(int $actorId, int $blogId, string $slug, string $title): int
    {
        $this->guard->ensure($actorId, $blogId);

        if ($this->pages->slugExists($blogId, $slug) || $this->pages->pageKeyExists($blogId, $slug)) {
            throw new DomainException('A page with this slug already exists in this blog.');
        }

        return $this->pages->create($blogId, $actorId, $slug, $slug, new PostDraft($title, null, TiptapDocument::empty()));
    }

    public function saveDraft(int $actorId, int $blogId, int $pageId, PostDraft $draft): void
    {
        $this->requirePage($actorId, $blogId, $pageId);
        $this->ensureContentMedia($blogId, $draft);
        $this->pages->saveDraft($blogId, $pageId, $draft, $actorId);
    }

    public function updateMeta(int $actorId, int $blogId, int $pageId, string $slug, ?string $seoTitle, ?string $seoDescription): void
    {
        $this->requirePage($actorId, $blogId, $pageId);

        if ($this->pages->slugExists($blogId, $slug, $pageId)) {
            throw new DomainException('A page with this slug already exists in this blog.');
        }

        $this->pages->updateMeta($blogId, $pageId, $slug, $seoTitle, $seoDescription, $actorId);
    }

    public function publish(int $actorId, int $blogId, int $pageId): void
    {
        $page = $this->requirePage($actorId, $blogId, $pageId);
        $this->ensureContentMedia($blogId, $page->editable());
        $this->pages->publish($blogId, $pageId, $actorId);
    }

    public function changeStatus(int $actorId, int $blogId, int $pageId, PostStatus $status): void
    {
        $this->requirePage($actorId, $blogId, $pageId);

        if (! in_array($status, [PostStatus::Draft, PostStatus::Archived], true)) {
            throw new DomainException('Use publish to make a page public.');
        }

        $this->pages->setStatus($blogId, $pageId, $status, $actorId);
    }

    public function delete(int $actorId, int $blogId, int $pageId): void
    {
        $this->requirePage($actorId, $blogId, $pageId);
        $this->pages->softDelete($blogId, $pageId, $actorId);
    }

    private function requirePage(int $actorId, int $blogId, int $pageId): PageRecord
    {
        $this->guard->ensure($actorId, $blogId);

        return $this->pages->find($blogId, $pageId) ?? throw new DomainException('Page not found.');
    }

    private function ensureContentMedia(int $blogId, PostDraft $draft): void
    {
        $ids = $draft->content->mediaIds();

        if (count($this->media->readyAssetsForBlog($blogId, $ids)) !== count($ids)) {
            throw new DomainException('The content contains an image that is not available in this blog.');
        }
    }
}
