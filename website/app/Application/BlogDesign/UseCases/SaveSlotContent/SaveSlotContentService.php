<?php

declare(strict_types=1);

namespace App\Application\BlogDesign\UseCases\SaveSlotContent;

use App\Application\BlogDesign\Contracts\BlogDesignRepository;
use App\Application\BlogDesign\Services\OwnedBlogGuard;
use App\Application\Content\Contracts\TaxonomyRepository;
use App\Application\Media\Contracts\MediaAssetRepository;
use App\Domain\Templates\TemplateSlotCatalog;
use DomainException;

final readonly class SaveSlotContentService
{
    public function __construct(
        private OwnedBlogGuard $guard,
        private BlogDesignRepository $designs,
        private MediaAssetRepository $media,
        private TaxonomyRepository $taxonomy,
    ) {}

    /** @param array<string, array<string, mixed>> $content */
    public function execute(int $actorId, int $blogId, array $content): void
    {
        $this->guard->ensure($actorId, $blogId);
        $design = $this->designs->find($blogId);
        $base = $design?->draftData ?? $design?->publishedData;

        if ($base === null) {
            throw new DomainException('Choose a template before editing homepage content.');
        }

        $slots = [];
        foreach ($base->sections as $slot) {
            $slots[$slot->key] = $slot;
        }
        // The Studio saves one region at a time. Preserve content belonging to
        // the other fixed regions in the same design draft.
        $sanitized = $base->slotContent;
        foreach ($content as $key => $values) {
            if (! isset($slots[$key]) || ! is_array($values)) {
                continue;
            }
            $allowed = array_flip(TemplateSlotCatalog::fields($slots[$key]->section));
            $slotValues = array_intersect_key($values, $allowed);
            foreach (['mediaIds', 'categoryIds'] as $listField) {
                if (isset($slotValues[$listField]) && is_string($slotValues[$listField])) {
                    $slotValues[$listField] = array_values(array_filter(array_map('intval', explode(',', $slotValues[$listField]))));
                }
            }
            foreach (array_filter([
                $slotValues['mediaId'] ?? null,
                ...($slotValues['mediaIds'] ?? []),
            ]) as $mediaId) {
                if (! $this->media->isUsableForBlog((int) $mediaId, $blogId)) {
                    throw new DomainException('Selected media does not belong to this blog or is not ready.');
                }
            }
            foreach (array_filter([
                $slotValues['categoryId'] ?? null,
                ...($slotValues['categoryIds'] ?? []),
            ]) as $categoryId) {
                if ($this->taxonomy->findCategory($blogId, (int) $categoryId) === null) {
                    throw new DomainException('Selected category does not belong to this blog.');
                }
            }
            $sanitized[$key] = $slotValues;
        }

        $this->designs->saveDraft($blogId, $base->withSlotContent($sanitized), $actorId);
    }
}
