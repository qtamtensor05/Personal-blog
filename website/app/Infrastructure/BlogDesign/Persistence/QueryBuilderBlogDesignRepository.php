<?php

declare(strict_types=1);

namespace App\Infrastructure\BlogDesign\Persistence;

use App\Application\BlogDesign\Contracts\BlogDesignRepository;
use App\Application\BlogDesign\DTOs\BlogDesignRecord;
use App\Domain\BlogDesign\ValueObjects\BlogDesignData;
use Illuminate\Support\Facades\DB;

final class QueryBuilderBlogDesignRepository implements BlogDesignRepository
{
    private const TABLE = 'blog_designs';

    public function find(int $blogId): ?BlogDesignRecord
    {
        $row = DB::table(self::TABLE)->where('blog_id', $blogId)->first();

        if ($row === null) {
            return null;
        }

        return new BlogDesignRecord(
            blogId: (int) $row->blog_id,
            templateId: $row->template_id !== null ? (int) $row->template_id : null,
            draftTemplateId: $row->draft_template_id !== null
                ? (int) $row->draft_template_id
                : null,
            publishedData: $row->published_data !== null
                ? BlogDesignData::fromArray(json_decode($row->published_data, true))
                : null,
            draftData: $row->draft_data !== null
                ? BlogDesignData::fromArray(json_decode($row->draft_data, true))
                : null,
            publishedAt: $row->published_at,
        );
    }

    public function saveDraft(int $blogId, BlogDesignData $draft, int $updatedBy): void
    {
        $now = now();

        DB::table(self::TABLE)->upsert(
            [
                'blog_id' => $blogId,
                'draft_template_id' => $draft->templateId,
                'draft_data' => json_encode($draft),
                'updated_by' => $updatedBy,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['blog_id'],
            ['draft_template_id', 'draft_data', 'updated_by', 'updated_at'],
        );
    }

    public function publishRegion(int $blogId, string $region, int $updatedBy): void
    {
        $record = $this->find($blogId);
        $draft = $record?->draftData;

        if ($draft === null) {
            return;
        }

        $published = $record?->publishedData ?? $draft->withSlotContent([]);
        $content = $published->slotContent;

        foreach ($draft->sections as $slot) {
            if ($slot->region !== $region) {
                continue;
            }

            if (array_key_exists($slot->key, $draft->slotContent)) {
                $content[$slot->key] = $draft->slotContent[$slot->key];
            } else {
                unset($content[$slot->key]);
            }
        }

        $published = $published->withSlotContent($content);

        DB::table(self::TABLE)
            ->where('blog_id', $blogId)
            ->update([
                'template_id' => $draft->templateId,
                'published_data' => json_encode($published),
                'published_at' => now(),
                'updated_by' => $updatedBy,
                'updated_at' => now(),
            ]);
    }
}
