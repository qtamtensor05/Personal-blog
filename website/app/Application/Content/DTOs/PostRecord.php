<?php

declare(strict_types=1);

namespace App\Application\Content\DTOs;

use App\Domain\Content\Enums\PostStatus;
use App\Domain\Content\ValueObjects\PostDraft;
use App\Domain\Content\ValueObjects\TiptapDocument;

final readonly class PostRecord
{
    /**
     * @param  list<int>  $categoryIds
     * @param  list<int>  $tagIds
     */
    public function __construct(
        public int $id,
        public int $blogId,
        public int $authorUserId,
        public string $title,
        public string $slug,
        public ?string $excerpt,
        public TiptapDocument $content,
        public ?PostDraft $draft,
        public PostStatus $status,
        public ?int $coverMediaId,
        public ?int $ogMediaId,
        public ?string $seoTitle,
        public ?string $seoDescription,
        public bool $isFeatured,
        public ?string $publishedAt,
        public string $updatedAt,
        public array $categoryIds = [],
        public array $tagIds = [],
    ) {}

    /**
     * What the editor should show: the draft if there is one, otherwise the live version.
     */
    public function editable(): PostDraft
    {
        return $this->draft ?? new PostDraft($this->title, $this->excerpt, $this->content);
    }
}
