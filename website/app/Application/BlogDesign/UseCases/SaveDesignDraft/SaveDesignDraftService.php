<?php

declare(strict_types=1);

namespace App\Application\BlogDesign\UseCases\SaveDesignDraft;

use App\Application\BlogDesign\Contracts\BlogDesignRepository;
use App\Application\BlogDesign\Services\OwnedBlogGuard;
use App\Domain\Templates\ValueObjects\TemplateDesignTokens;
use DomainException;

/**
 * MVP customization scope: the owner may change design tokens (colors, fonts) only.
 * Sections and layout come from the template snapshot.
 */
final readonly class SaveDesignDraftService
{
    public function __construct(
        private OwnedBlogGuard $guard,
        private BlogDesignRepository $designs,
    ) {}

    public function execute(int $actorId, int $blogId, TemplateDesignTokens $tokens): void
    {
        $this->guard->ensure($actorId, $blogId);

        $design = $this->designs->find($blogId);
        $base = $design?->draftData ?? $design?->publishedData;

        if ($base === null) {
            throw new DomainException('Choose a template before customizing the design.');
        }

        $this->designs->saveDraft($blogId, $base->withDesignTokens($tokens), $actorId);
    }
}
