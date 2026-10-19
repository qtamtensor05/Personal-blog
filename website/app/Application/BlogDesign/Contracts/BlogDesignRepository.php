<?php

declare(strict_types=1);

namespace App\Application\BlogDesign\Contracts;

use App\Application\BlogDesign\DTOs\BlogDesignRecord;
use App\Domain\BlogDesign\ValueObjects\BlogDesignData;

interface BlogDesignRepository
{
    public function find(int $blogId): ?BlogDesignRecord;

    /**
     * Write draft_template_id + draft_data together (ck_blog_designs_2), creating the row if needed.
     */
    public function saveDraft(int $blogId, BlogDesignData $draft, int $updatedBy): void;

    /** Publish only the slot content that belongs to one fixed template region. */
    public function publishRegion(int $blogId, string $region, int $updatedBy): void;
}
