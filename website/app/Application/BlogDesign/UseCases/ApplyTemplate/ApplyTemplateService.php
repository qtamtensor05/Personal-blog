<?php

declare(strict_types=1);

namespace App\Application\BlogDesign\UseCases\ApplyTemplate;

use App\Application\BlogDesign\Contracts\BlogDesignRepository;
use App\Application\BlogDesign\Services\OwnedBlogGuard;
use App\Application\Templates\Contracts\TemplateRepository;
use App\Application\Templates\Exceptions\TemplateNotFound;
use App\Domain\BlogDesign\ValueObjects\BlogDesignData;
use App\Domain\Templates\Enums\TemplateStatus;
use DomainException;

/**
 * Puts the first published template into the blog draft. The owner can preview
 * this draft at the blog URL while visitors continue to see published data.
 */
final readonly class ApplyTemplateService
{
    public function __construct(
        private OwnedBlogGuard $guard,
        private TemplateRepository $templates,
        private BlogDesignRepository $designs,
    ) {}

    public function execute(int $actorId, int $blogId, int $templateId): void
    {
        $this->guard->ensure($actorId, $blogId);

        $existing = $this->designs->find($blogId);
        if ($existing?->draftData !== null || $existing?->publishedData !== null) {
            throw new DomainException(
                'This blog already has a template. Its fixed layout cannot be changed.',
            );
        }

        $template = $this->templates->find($templateId);

        // Only PUBLISHED templates can be chosen; HIDDEN/RETIRED stay on blogs that already use them.
        if (
            $template === null ||
            $template->status !== TemplateStatus::Published ||
            $template->publishedData === null
        ) {
            throw new TemplateNotFound;
        }

        $this->designs->saveDraft(
            $blogId,
            BlogDesignData::fromTemplate(
                $template->id,
                $template->versionNumber,
                $template->publishedData,
            ),
            $actorId,
        );
    }
}
