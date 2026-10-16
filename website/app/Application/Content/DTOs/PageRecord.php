<?php

declare(strict_types=1);

namespace App\Application\Content\DTOs;

use App\Domain\Content\Enums\PostStatus;
use App\Domain\Content\ValueObjects\PostDraft;
use App\Domain\Content\ValueObjects\TiptapDocument;

/**
 * Static page. pages.status uses the same enum values as posts, so PostStatus is reused.
 */
final readonly class PageRecord
{
    public function __construct(
        public int $id,
        public int $blogId,
        public string $title,
        public string $slug,
        public string $pageKey,
        public TiptapDocument $content,
        public ?PostDraft $draft,
        public PostStatus $status,
        public ?string $seoTitle,
        public ?string $seoDescription,
        public ?string $publishedAt,
        public string $updatedAt,
    ) {}

    public function editable(): PostDraft
    {
        return $this->draft ?? new PostDraft($this->title, null, $this->content);
    }
}
