<?php

declare(strict_types=1);

namespace App\Application\BlogDesign\UseCases\ApplyTemplate;

use App\Application\BlogDesign\Contracts\BlogDesignRepository;
use App\Application\BlogDesign\Services\OwnedBlogGuard;
use App\Application\Templates\Contracts\TemplateRepository;
use App\Application\Templates\Exceptions\TemplateNotFound;
use App\Domain\BlogDesign\ValueObjects\BlogDesignData;
use App\Domain\Templates\Enums\TemplateStatus;

/**
 * Puts a snapshot of a published template into the blog's design draft.
 * The live design is untouched until the owner publishes.
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
            BlogDesignData::fromTemplate($template->id, $template->versionNumber, $template->publishedData),
            $actorId,
        );
    }
}
